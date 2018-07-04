<?php
/**
 * This file is part of the Ztech AttributeImport package.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Ztech AttributeImport
 * to newer versions in the future.
 *
 * @copyright Copyright (c) 2018 Zilker Technology, Ltd. (https://ztech.io/)
 * @license   GNU General Public License ("GPL") v3.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ztech\AttributeImport\Model;

use Exception;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\File\Csv;

/**
 * Class AttributeImport.
 */
class AttributeImport implements AttributeImportInterface
{
    const BEHAVIOR_INSERT = 1;
    const BEHAVIOR_UPDATE = 0;

    /**
     * @var ComponentRegistrar
     */
    protected $componentRegistrar;

    /**
     * @var Csv
     */
    protected $csvProcessor;

    /**
     * @var EavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var AttributeOptionManagementInterface
     */
    protected $optionManagement;

    /**
     * @var AttributeManagementInterface
     */
    protected $attributeManagement;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    protected $attributeRepository;

    /**
     * @var array
     */
    protected $changesOnUpdate = [
        'user_defined',
        'visible',
        'global',
        'searchable',
        'filterable',
        'visible_on_front',
        'used_in_product_listing',
        'required',
        'comparable',
        'visible_in_advanced_search',
        'filterable_in_search'
    ];

    /**
     * InstallSchema constructor.
     *
     * @param ComponentRegistrar $componentRegistrar
     * @param Csv $csvProcessor
     * @param EavSetupFactory $eavSetupFactory
     * @param AttributeOptionManagementInterface $optionManagement
     * @param AttributeManagementInterface $attributeManagement
     * @param ProductAttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        Csv $csvProcessor,
        EavSetupFactory $eavSetupFactory,
        AttributeOptionManagementInterface $optionManagement,
        AttributeManagementInterface $attributeManagement,
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->csvProcessor = $csvProcessor;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->optionManagement = $optionManagement;
        $this->attributeManagement = $attributeManagement;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Process attribute import.
     *
     * @param string|null $content
     * @param bool|null $behaviour
     *
     * @return void
     * @throws LocalizedException
     */
    public function import($content = null, $behaviour = null)
    {
        $errors = [];

        foreach ($this->parseData($content) as $attribute) {
            try {
                if ($behaviour === 'delete') {
                    $this->attributeRepository->deleteById($attribute['attribute_code']);
                } else {
                    switch ($this->getBehavior($attribute)) {
                        case self::BEHAVIOR_INSERT:
                            $this->processInsert($attribute);
                            break;

                        case self::BEHAVIOR_UPDATE:
                            $this->processUpdate($attribute);
                            break;
                    }
                }
            } catch (LocalizedException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            throw new LocalizedException(__(implode(PHP_EOL, $errors)));
        }
    }

    /**
     * Parse CSV data file.
     *
     * @param string|null $content
     *
     * @return array
     */
    protected function parseData($content)
    {
        if (!$content) {
            $content = $this->componentRegistrar
                    ->getPath(ComponentRegistrar::MODULE, 'Ztech_AttributeImport') . '/Files/attributes.csv';
        }

        try {
            $rows = $this->csvProcessor->getData($content);
        } catch (Exception $e) {
            return [];
        }

        $headers = array_shift($rows);
        $data = [];

        foreach ($rows as $row) {
            $tmp = [];

            for ($i = 0; $i < count($row); $i++) {
                $tmp[$headers[$i]] = $row[$i];
            }

            $this->formatValues($tmp);

            $data[] = $tmp;
        }

        return $data;
    }

    /**
     * Format values.
     *
     * @param array $data
     *
     * @return void
     */
    protected function formatValues(array &$data)
    {
        if (!isset($data['values']) && !empty($data['values'])) {
            return;
        }

        $values = explode(',', $data['values']);
        unset($data['values']);

        $data['option']['values'] = $values;
    }

    /**
     * Get import behaviour.
     *
     * @param array $data
     *
     * @return bool
     */
    protected function getBehavior(array $data)
    {
        $eavSetup = $this->eavSetupFactory->create();

        if (empty($eavSetup->getAttribute($data['entity_type'], $data['attribute_code']))) {
            return self::BEHAVIOR_INSERT;
        }

        return self::BEHAVIOR_UPDATE;
    }

    /**
     * Process attribute insert.
     *
     * @param $attribute
     *
     * @return void
     * @throws LocalizedException
     */
    protected function processInsert($attribute)
    {
        $attributeCode = $attribute['attribute_code'];
        $entityType = $attribute['entity_type'];

        unset($attribute['attribute_code'], $attribute['entity_type']);

        $eavSetup = $this->eavSetupFactory->create();
        $eavSetup->addAttribute($entityType, $attributeCode, $attribute);

        if (!empty($attribute['attribute_set']) && !empty($attribute['attribute_set_group'])) {
            $this->processAttributeSet($eavSetup, $entityType, $attributeCode, $attribute);
        }
    }

    /**
     * Process attribute update.
     *
     * @param $attribute
     *
     * @return void
     * @throws LocalizedException
     */
    protected function processUpdate($attribute)
    {
        $attributeCode = $attribute['attribute_code'];
        $entityType = $attribute['entity_type'];
        $options = $attribute['option'];

        unset($attribute['attribute_code'], $attribute['entity_type'], $attribute['option']);

        $eavSetup = $this->eavSetupFactory->create();

        foreach ($attribute as $key => $value) {
            if ($value == '') {
                continue;
            }

            if (in_array($key, $this->changesOnUpdate)) {
                $key = 'is_' . $key;
            }

            $eavSetup->updateAttribute($entityType, $attributeCode, $key, $value);
        }

        if (!empty($options)) {
            $origOptions = $this->optionManagement->getItems($eavSetup->getEntityTypeId($entityType), $attributeCode);

            foreach ($origOptions as $origOption) {
                if (
                    in_array($origOption->getLabel(), $options['values']) &&
                    ($key = array_search($origOption->getLabel(), $options['values'])) !== false
                ) {
                    unset($options['values'][$key]);
                }
            }

            $eavSetup->addAttributeOption(array_merge(
                ['attribute_id' => $eavSetup->getAttributeId($entityType, $attributeCode)],
                $options
            ));
        }

        if (!empty($attribute['attribute_set']) && !empty($attribute['attribute_set_group'])) {
            $this->processAttributeSet($eavSetup, $entityType, $attributeCode, $attribute);
        }
    }

    /**
     * Process attribute set and groups.
     *
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @param string $entityType
     * @param string $attributeCode
     * @param array $attribute
     *
     * @return void
     * @throws LocalizedException
     */
    protected function processAttributeSet($eavSetup, $entityType, $attributeCode, $attribute)
    {
        $entityTypeId = $eavSetup->getEntityTypeId($entityType);
        $sets = explode(',', $attribute['attribute_set']);
        $groups = explode(',', $attribute['attribute_set_group']);
        $setData = [];

        foreach ($groups as $group) {
            // [$setName, $groupName] = explode('=', $group);
            // $setData[$setName] = $groupName;
            print_r($group);
        }

        die();

        try {
            $this->attributeManagement->unassign($eavSetup->getAttributeSetId($entityTypeId, 'Default'), $attributeCode);
        } catch (InputException | NoSuchEntityException | StateException $e) {
            // Skip intentionally
        }

        foreach ($sets as $set) {
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $set,
                $setData[$set] ?? 'General',
                $attributeCode,
                1000
            );
        }
    }
}
