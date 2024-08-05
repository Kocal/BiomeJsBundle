<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
final class BiomeJs
{
    private ?SymfonyStyle $output;

    public function __construct(
        private readonly BiomeJsBinaryInterface $biomeJsBinary,
    ) {
    }

    public function setOutput(?SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    /**
     * @param array<string> $path
     */
    public function check(
        bool $write,
        bool $unsafe,
        bool $formatterEnabled,
        bool $linterEnabled,
        bool $organizeImportsEnabled,
        bool $staged,
        bool $changed,
        ?string $since,
        array $path,
    ): Process {
        $arguments = [];
        if ($write) {
            $arguments[] = '--write';
        }
        if ($unsafe) {
            $arguments[] = '--unsafe';
        }
        $arguments[] = '--formatter-enabled=' . ($formatterEnabled ? 'true' : 'false');
        $arguments[] = '--linter-enabled=' . ($linterEnabled ? 'true' : 'false');
        $arguments[] = '--organize-imports-enabled=' . ($organizeImportsEnabled ? 'true' : 'false');
        if ($staged) {
            $arguments[] = '--staged';
        }
        if ($changed) {
            $arguments[] = '--changed';
        }
        if ($since) {
            $arguments[] = '--since=' . $since;
        }
        $arguments = ['check', ...$arguments, ...$path];

        $this->biomeJsBinary->setOutput($this->output);
        $process = $this->biomeJsBinary->createProcess($arguments);

        $this->output?->note('Executing Biome.js "check" (pass -v to see more details).');
        if ($this->output?->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    ' . $process->getCommandLine(),
            ]);
        }
        $process->start();

        return $process;
    }

    /**
     * @param array<string> $path
     */
    public function ci(
        bool $formatterEnabled,
        bool $linterEnabled,
        bool $organizeImportsEnabled,
        bool $changed,
        ?string $since,
        array $path,
    ): Process {
        $arguments = [];
        $arguments[] = '--formatter-enabled=' . ($formatterEnabled ? 'true' : 'false');
        $arguments[] = '--linter-enabled=' . ($linterEnabled ? 'true' : 'false');
        $arguments[] = '--organize-imports-enabled=' . ($organizeImportsEnabled ? 'true' : 'false');
        if ($changed) {
            $arguments[] = '--changed';
        }
        if ($since) {
            $arguments[] = '--since=' . $since;
        }
        $arguments = ['ci', ...$arguments, ...$path];

        $this->biomeJsBinary->setOutput($this->output);
        $process = $this->biomeJsBinary->createProcess($arguments);

        $this->output?->note('Executing Biome.js "ci" (pass -v to see more details).');
        if ($this->output?->isVerbose()) {
            $this->output->writeln([
                '  Command:',
                '    ' . $process->getCommandLine(),
            ]);
        }
        $process->start();

        return $process;
    }
}
