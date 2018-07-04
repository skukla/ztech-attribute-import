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

namespace Ztech\AttributeImport\Controller\Adminhtml\Import;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Ztech\AttributeImport\Model\AttributeImportInterface\Proxy as AttributeImportInterface;
use Ztech\AttributeImport\Model\AttributeSetImportInterface\Proxy as AttributeSetImportInterface;

/**
 * Class Save.
 */
class Save extends Action
{
    /**
     * @var UploaderFactory
     */
    protected $uploaderFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var AttributeImportInterface
     */
    protected $attributeImport;

    /**
     * @var AttributeSetImportInterface
     */
    protected $attributeSetImport;

    /**
     * Save constructor.
     *
     * @param Action\Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param AttributeImportInterface $attributeImport
     * @param AttributeSetImportInterface $attributeSetImport
     */
    public function __construct(
        Action\Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        AttributeImportInterface $attributeImport,
        AttributeSetImportInterface $attributeSetImport
    ) {
        parent::__construct($context);

        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->attributeImport = $attributeImport;
        $this->attributeSetImport = $attributeSetImport;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'data_import_file']);
            $read = $this->filesystem->getDirectoryRead(DirectoryList::TMP);
            $write = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);

            $uploader->setAllowRenameFiles(true);
            $result = $uploader->save($read->getAbsolutePath('ztech_import'));
            $path = $read->getAbsolutePath('ztech_import/' . $result['file']);

            switch ($data['entity']) {
                case 'attributes':
                    $this->attributeImport->import($path, $data['behaviour']);
                    break;

                case 'attribute_sets':
                    $this->attributeSetImport->import($path, $data['behaviour']);
                    break;
            }

            $write->delete($path);
            $this->messageManager->addSuccessMessage('Data imported successfully!');
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Failed to import data: ' . $e->getMessage()));
        } finally {
            return $this->resultRedirectFactory->create()->setPath('*/*');
        }
    }
}
