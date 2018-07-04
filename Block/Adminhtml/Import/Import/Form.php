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

namespace Ztech\AttributeImport\Block\Adminhtml\Import\Import;

use Magento\Backend\Block\Widget\Form\Generic;

/**
 * Class Form.
 */
class Form extends Generic
{
    /**
     * Prepare form.
     *
     * @return Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ]
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Attribute Import')]);

        $fieldset->addField(
            'entity',
            'select',
            [
                'name' => 'entity',
                'title' => __('Entity Type'),
                'label' => __('Entity Type'),
                'required' => false,
                'onchange' => 'varienExport.getFilter();',
                'values' => [
                    [
                        'value' => 'attributes',
                        'label' => 'Attributes'
                    ],
                    [
                        'value' => 'attribute_sets',
                        'label' => 'Attribute Sets'
                    ]
                ]
            ]
        );

        $fieldset->addField(
            'behaviour',
            'select',
            [
                'name' => 'behaviour',
                'title' => __('Behaviour'),
                'label' => __('Behaviour'),
                'required' => false,
                'onchange' => 'varienExport.getFilter();',
                'values' => [
                    [
                        'value' => 'insert',
                        'label' => 'Insert'
                    ],
                    [
                        'value' => 'delete',
                        'label' => 'Delete'
                    ]
                ]
            ]
        );

        $fieldset->addField(
            'data_import_file',
            'file',
            [
                'name' => 'data_import_file',
                'label' => __('CSV File'),
                'title' => __('CSV File')
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
