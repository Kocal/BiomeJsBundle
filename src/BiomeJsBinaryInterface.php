<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * @deprecated will be removed in the next major version
 */
interface BiomeJsBinaryInterface
{
    public function setOutput(?SymfonyStyle $output): void;

    /**
     * @param array<string> $arguments
     *
     * @throws \Exception
     */
    public function createProcess(array $arguments = []): Process;
}
