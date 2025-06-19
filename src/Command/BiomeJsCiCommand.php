<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Command;

use Kocal\BiomeJsBundle\BiomeJs;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'biomejs:ci',
    description: 'Command to use in CI environments. Runs Biome.js formatter, linter and import sorting to the requested files',
)]
final class BiomeJsCiCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly BiomeJs $biomeJs,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('formatter-enabled', null, InputOption::VALUE_OPTIONAL, 'Allow to enable or disable the formatter check', true)
            ->addOption('linter-enabled', null, InputOption::VALUE_OPTIONAL, 'Allow to enable or disable the linter check', true)
            ->addOption('organize-imports-enabled', null, InputOption::VALUE_OPTIONAL, 'Allow to enable or disable the organize imports.', true)
            ->addOption('changed', null, InputOption::VALUE_NONE, 'When set to true, only the files that have been changed compared to your `defaultBranch` configuration will be linted.')
            ->addOption('since', null, InputOption::VALUE_OPTIONAL, "Use this to specify the base branch to compare against when you're using the --changed flag and the `defaultBranch` is not set in your biome.json.")
            ->addArgument('path', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Single file, single path or list of paths')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        trigger_deprecation('kocal/biome-js-bundle', '1.5', 'The "%s" command is deprecated and will be removed in the next major version, use "biomejs:download" instead.', $this->getName());

        $this->biomeJs->setOutput($this->io);

        $process = $this->biomeJs->ci(
            formatterEnabled: filter_var($input->getOption('formatter-enabled'), FILTER_VALIDATE_BOOL),
            linterEnabled: filter_var($input->getOption('linter-enabled'), FILTER_VALIDATE_BOOL),
            organizeImportsEnabled: filter_var($input->getOption('organize-imports-enabled'), FILTER_VALIDATE_BOOL),
            changed: $input->getOption('changed'),
            since: $input->getOption('since'),
            path: $input->getArgument('path'),
        );

        $process->wait(fn ($type, $buffer) => $this->io->write($buffer));

        if (!$process->isSuccessful()) {
            $this->io->error('Biome.js ci failed: see output above.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
