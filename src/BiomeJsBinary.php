<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BiomeJsBinary
{
    private ?SymfonyStyle $output = null;
    private HttpClientInterface $httpClient;
    private ?string $cachedVersion = null;

    public function __construct(
        private readonly string $cwd,
        private readonly string $binaryDownloadDir,
        private readonly ?string $binaryVersion,
        ?HttpClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function setOutput(?SymfonyStyle $output): void
    {
        $this->output = $output;
    }

    /**
     * @param array<string> $arguments
     *
     * @throws \Exception
     */
    public function createProcess(array $arguments = []): Process
    {
        $binary = $this->binaryDownloadDir . '/' . $this->getVersion() . '/' . self::getBinaryName();
        if (!is_file($binary)) {
            $this->downloadExecutable();
        }

        return new Process([$binary, ...$arguments], $this->cwd);
    }

    private function downloadExecutable(): void
    {
        $url = sprintf('https://github.com/biomejs/biome/releases/download/cli/%s/%s', $this->getVersion(), self::getBinaryName());

        $this->output?->note(sprintf('Downloading Biome.js binary from %s', $url));

        if (!is_dir($downloadDir = $this->binaryDownloadDir . '/' . $this->getVersion())) {
            mkdir($downloadDir, 0777, true);
        }

        $targetPath = $downloadDir . '/' . self::getBinaryName();
        $progressBar = null;

        $response = $this->httpClient->request('GET', $url, [
            'on_progress' => function (int $dlNow, int $dlSize, array $info) use (&$progressBar): void {
                // dlSize is not known at the start
                if (0 === $dlSize) {
                    return;
                }

                if (!$progressBar) {
                    $progressBar = $this->output?->createProgressBar($dlSize);
                }

                $progressBar?->setProgress($dlNow);
            },
        ]);
        $fileHandler = fopen($targetPath, 'w');
        if (!is_resource($fileHandler)) {
            throw new \Exception(sprintf('Cannot open file "%s" for writing.', $targetPath));
        }
        foreach ($this->httpClient->stream($response) as $chunk) {
            fwrite($fileHandler, $chunk->getContent());
        }
        fclose($fileHandler);
        $progressBar?->finish();
        $this->output?->writeln('');
        // make file executable
        chmod($targetPath, 0777);
    }

    private function getVersion(): string
    {
        return $this->cachedVersion ??= $this->binaryVersion ?? $this->getLatestVersion();
    }

    private function getLatestVersion(): string
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.github.com/repos/biomejs/biome/releases');
            foreach ($response->toArray() as $release) {
                if (str_starts_with($release['tag_name'], 'cli/')) {
                    return str_replace('cli/', '', $release['tag_name']);
                }
            }

            throw new \Exception('Unable to find the latest Biome.js CLI release.');
        } catch (\Throwable $e) {
            throw new \Exception('Cannot determine latest Biome.js binary version. Please specify a version in the configuration.', previous: $e);
        }
    }

    /**
     * @internal
     */
    public static function getBinaryName(): string
    {
        $os = strtolower(\PHP_OS);
        $machine = strtolower(php_uname('m'));

        return match (true) {
            str_contains($os, 'darwin') => match ($machine) {
                'arm64' => 'biome-darwin-arm64',
                'x86_64' => 'biome-darwin-x64',
                default => throw new \Exception(sprintf('No matching machine found for Darwin platform (Machine: %s).', $machine)),
            },
            str_contains($os, 'linux') => match ($machine) {
                'arm64', 'aarch64' => 'biome-linux-arm64',
                'x86_64' => 'biome-linux-x64',
                default => throw new \Exception(sprintf('No matching machine found for Linux platform (Machine: %s).', $machine)),
            },
            str_contains($os, 'win') => match ($machine) {
                'arm64' => 'biome-win32-arm64.exe',
                'x86_64', 'amd64' => 'biome-win32-x64.exe',
                default => throw new \Exception(sprintf('No matching machine found for Windows platform (Machine: %s).', $machine)),
            },
            default => throw new \Exception(sprintf('Unknown platform or architecture (OS: %s, Machine: %s).', $os, $machine)),
        };
    }
}
