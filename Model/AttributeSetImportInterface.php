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

/**
 * Interface AttributeSetImportInterface.
 */
interface AttributeSetImportInterface
{
    /**
     * Process attribute set import.
     *
     * @param string|null $content
     * @param bool|null $behaviour
     *
     * @return void
     */
    public function import($content = null, $behaviour = null);
}
