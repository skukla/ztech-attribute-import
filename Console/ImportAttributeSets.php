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

namespace Ztech\AttributeImport\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ztech\AttributeImport\Model\AttributeSetImportInterface;

/**
 * Class ImportAttributeSets.
 */
class ImportAttributeSets extends Command
{
    /**
     * @var AttributeSetImportInterface
     */
    protected $attributeSetImport;

    /**
     * Import constructor.
     *
     * @param AttributeSetImportInterface $attributeSetImport
     */
    public function __construct(AttributeSetImportInterface $attributeSetImport)
    {
        parent::__construct();

        $this->attributeSetImport = $attributeSetImport;
    }

    /**
     * Configure command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('import:attribute-sets')
            ->setDescription('Import attribute sets');
    }

    /**
     * Execute command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->attributeSetImport->import();
    }
}
