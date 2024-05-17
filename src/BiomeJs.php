<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class BiomeJs
{
    private ?SymfonyStyle $output;

    public function __construct(
        private readonly BiomeJsBinary $biomeJsBinary,
        private readonly bool $useTty = true,
    ) {
    }

    public function setOutput(SymfonyStyle|null $output): void
    {
        $this->output = $output;
    }

    /**
     * @param array<string> $path
     */
    public function check(
        bool $apply,
        bool $applyUnsafe,
        bool $formatterEnabled,
        bool $linterEnabled,
        bool $organizeImportsEnabled,
        bool $staged,
        bool $changed,
        string|null $since,
        array $path,
    ): Process {
        $arguments = [];
        if ($apply) {
            $arguments[] = '--apply';
        }
        if ($applyUnsafe) {
            $arguments[] = '--apply-unsafe';
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
        $process->setTty($this->useTty);

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
        string|null $since,
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
        $process->setTty($this->useTty);

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
