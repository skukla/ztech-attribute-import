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

namespace Ztech\AttributeImport\Block\Adminhtml\Import;

use Magento\Backend\Block\Widget\Form\Container;

class Import extends Container
{
    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->removeButton('back')->removeButton('reset');
        $this->updateButton('save', 'label', __('Import Data'));

        $this->_blockGroup = 'Ztech_AttributeImport';
        $this->_controller = 'adminhtml_import';
        $this->_mode = 'import';
    }

    /**
     * Get header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Import Attributes / Attribute Sets');
    }
}
