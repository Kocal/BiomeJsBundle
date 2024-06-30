<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Tests\fixtures;

use Kocal\BiomeJsBundle\BiomeJsBinaryInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class TestBiomeJsBinary implements BiomeJsBinaryInterface
{
    public function __construct(
        private readonly BiomeJsBinaryInterface $inner,
    ) {
    }

    public function setOutput(?SymfonyStyle $output): void
    {
        $this->inner->setOutput($output);
    }

    public function createProcess(array $arguments = []): Process
    {
        $arguments[] = '--colors=off';

        $process = $this->inner->createProcess($arguments);
        // Disable TTY to avoid issues when testing
        $process->setTty(false);

        return $process;
    }
}
