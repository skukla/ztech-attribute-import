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
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;

/**
 * Class AttributeSetImport.
 */
class AttributeSetImport implements AttributeSetImportInterface
{
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
     * @var SetFactory
     */
    protected $setFactory;

    /**
     * @var AttributeSetRepositoryInterface
     */
    protected $attributeSetRepository;

    /**
     * AttributeSetImport constructor.
     *
     * @param ComponentRegistrar $componentRegistrar
     * @param Csv $csvProcessor
     * @param EavSetupFactory $eavSetupFactory
     * @param SetFactory $setFactory
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        ComponentRegistrar $componentRegistrar,
        Csv $csvProcessor,
        EavSetupFactory $eavSetupFactory,
        SetFactory $setFactory,
        AttributeSetRepositoryInterface $attributeSetRepository
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->csvProcessor = $csvProcessor;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->setFactory = $setFactory;
        $this->attributeSetRepository = $attributeSetRepository;
    }

    /**
     * Process attribute set import.
     *
     * @param string|null $content
     * @param bool|null $behaviour
     *
     * @return void
     * @throws LocalizedException
     */
    public function import($content = null, $behaviour = null)
    {
        foreach ($this->parseData($content) as $attributeSet) {
            switch ($behaviour) {
                case 'insert':
                    $this->processInsert($attributeSet);
                    break;

                case 'delete':
                    $this->deleteSet($attributeSet);
                    break;

                default:
                    $this->deleteSet($attributeSet);
                    $this->processInsert($attributeSet);
            }
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
                    ->getPath(ComponentRegistrar::MODULE, 'Ztech_AttributeImport') . '/Files/attribute_sets.csv';
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

            $data[] = $tmp;
        }

        return $data;
    }

    /**
     * Create attribute set and groups.
     *
     * @param $data
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processInsert($data)
    {
        $eavSetup = $this->eavSetupFactory->create();

        $attributeSet = $this->setFactory->create();
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $defaultAttributeSetId = empty($data['based_on']) ?
            $eavSetup->getDefaultAttributeSetId($entityTypeId) :
            $eavSetup->getAttributeSetId($entityTypeId, $data['based_on']);

        $attributeSet->setData([
            'attribute_set_name' => $data['name'],
            'entity_type_id' => $entityTypeId,
            'sort_order' => $data['sort_order']
        ]);
        $attributeSet->validate();
        $this->attributeSetRepository->save($attributeSet);

        $attributeSet->initFromSkeleton($defaultAttributeSetId);
        $this->attributeSetRepository->save($attributeSet);

        if (!empty($data['groups'])) {
            $groups = explode(',', $data['groups']);

            foreach ($groups as $group) {
                $eavSetup->addAttributeGroup($entityTypeId, $data['name'], $group, 20);
            }
        }
    }

    /**
     * Delete attribute set before importing new one.
     *
     * @param $data
     *
     * @return void
     */
    protected function deleteSet($data)
    {
        $eavSetup = $this->eavSetupFactory->create();

        try {
            $this->attributeSetRepository->deleteById(
                $eavSetup->getAttributeSetId($eavSetup->getEntityTypeId(Product::ENTITY), $data['name'])
            );
        } catch (Exception $e) {
            // Skip intentionally
        }
    }
}
