<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

class StorageLinkCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'app:storage:link';
    protected static $defaultDescription = 'Make a symlink for the storage files path to serve';

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('source_path', InputArgument::REQUIRED, 'Source folder path')
            ->addOption(
                'target',
                't',
                InputOption::VALUE_REQUIRED,
                'Sub-folder target path for the public folder. '
                . 'If not set then parent name will be used of source folder'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sourcePath = rtrim($input->getArgument('source_path'), '/');
//        $sourcePath = '/' . trim($input->getArgument('source_path'), '/');

        $projectDir = $this->container->getParameter('kernel.project_dir');
        $fs = new Filesystem();

        if (!$fs->isAbsolutePath($sourcePath)) {
            $sourcePath = $projectDir . '/' . $sourcePath;
        }

        if (!$fs->exists($sourcePath)) {
            $io->error("Source folder path not found: $sourcePath");

            return 1;
        }

        if (!is_dir($sourcePath)) {
            $io->error("Expected to be a directory: $sourcePath");

            return 1;
        }

        $sourcePath = realpath($sourcePath);

        $io->comment("Source path is: <info>$sourcePath</info>");

        $targetPath = $projectDir . '/public/' .
            trim($this->container->getParameter('app_storage_public_path'), '/');
        if ($input->getOption('target')) {
            $targetPath .= '/' . trim($input->getOption('target'), '/');
        } else {
            $targetPath .= '/' . basename($sourcePath);
        }

        $io->comment("Target path is: <info>$targetPath</info>");

        $fs->symlink($sourcePath, $targetPath);

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}
