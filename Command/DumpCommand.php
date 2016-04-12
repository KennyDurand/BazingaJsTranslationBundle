<?php

namespace Bazinga\Bundle\JsTranslationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;

/**
 * @author Adrien Russo <adrien.russo.qc@gmail.com>
 */
class DumpCommand extends ContainerAwareCommand
{
    private $targetPath;
    private $skipDomains;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('bazinga:js-translation:dump')
            ->setDefinition([
                new InputArgument(
                    'target',
                    InputArgument::OPTIONAL,
                    'Override the target file to dump JS translation files in.'
                ),
                new InputOption(
                    'skip-domains',
                    'skip',
                    InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                    'Skips the specified domains.'
                )
            ])
            ->setDescription('Dumps all JS translation files to the filesystem');
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->targetPath = $input->getArgument('target') ?:
            sprintf('%s/../web/js/translations.js', $this->getContainer()->getParameter('kernel.root_dir'));
        $this->skipDomains = $input->getOption('skip-domains') ?: [];
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!is_dir($dir = dirname($this->targetPath))) {
            $output->writeln('<info>[dir+]</info>  ' . $dir);
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException('Unable to create directory ' . $dir);
            }
        }

        $output->writeln(sprintf(
            'Dumping translations in <comment>%s</comment>',
            $this->targetPath
        ));

        $this
            ->getContainer()
            ->get('bazinga.jstranslation.translation_dumper')
            ->dump($this->targetPath, $this->skipDomains);
    }
}
