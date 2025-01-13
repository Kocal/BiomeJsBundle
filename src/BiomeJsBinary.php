<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @internal
 */
final class BiomeJsBinary implements BiomeJsBinaryInterface
{
    private ?SymfonyStyle $output = null;
    private HttpClientInterface $httpClient;
    private ?string $cachedVersion = null;

    public const LATEST_STABLE_VERSION = 'latest_stable';
    public const LATEST_NIGHTLY_VERSION = 'latest_nightly';

    /**
     * @param string|self::LATEST_STABLE_VERSION|self::LATEST_NIGHTLY_VERSION|null $binaryVersion
     */
    public function __construct(
        private readonly string $cwd,
        private readonly string $binaryDownloadDir,
        private readonly ?string $binaryVersion,
        private readonly CacheItemPoolInterface $cache,
        ?HttpClientInterface $httpClient = null,
    ) {
        if (null === $this->binaryVersion) {
            trigger_deprecation('kocal/biome-js-bundle', '1.1', 'Not explicitly specifying a Biome.js CLI version is deprecated, use "latest_stable", "latest_nightly", or an explicit version (e.g.: "v1.8.3") instead.');
        }

        $this->httpClient = $httpClient ?? new RetryableHttpClient(HttpClient::create());
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

        $process = new Process([$binary, ...$arguments], $this->cwd);
        if ($process->isTtySupported()) {
            $process->setTty(true);
        }

        return $process;
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
        if (null !== $this->cachedVersion) {
            return $this->cachedVersion;
        }

        if (null === $this->binaryVersion || self::LATEST_STABLE_VERSION === $this->binaryVersion || self::LATEST_NIGHTLY_VERSION === $this->binaryVersion) {
            return $this->cachedVersion = $this->getLatestVersion();
        }

        return $this->cachedVersion = $this->binaryVersion;
    }

    private function getLatestVersion(): string
    {
        $useStable = null === $this->binaryVersion || 'latest_stable' === $this->binaryVersion;
        $useNightly = 'latest_nightly' === $this->binaryVersion;
        $cacheKey = sprintf(
            'binary.latest_version.%s.%s',
            match (true) {
                $useStable => 'stable',
                $useNightly => 'nightly',
                default => throw new \LogicException('Invalid configuration'),
            },
            self::getBinaryName(),
        );
        $cacheItem = $this->cache->getItem($cacheKey);

        if ($cachedLatestVersion = $cacheItem->get()) {
            \assert(\is_string($cachedLatestVersion));

            return $cachedLatestVersion;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.github.com/repos/biomejs/biome/releases');

            foreach ($response->toArray() as $release) {
                if (!str_starts_with($release['tag_name'], 'cli/')) {
                    continue;
                }

                if ($useStable && true === $release['prerelease']) {
                    continue;
                }

                if ($useNightly && false === $release['prerelease']) {
                    continue;
                }

                $latestVersion = str_replace('cli/', '', $release['tag_name']);

                $cacheItem->set($latestVersion);
                $cacheItem->expiresAfter(new \DateInterval('P1W'));
                $this->cache->save($cacheItem);

                return $latestVersion;
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
                'arm64', 'aarch64' => self::isMusl() ? 'biome-linux-arm64-musl' : 'biome-linux-arm64',
                'x86_64' => self::isMusl() ? 'biome-linux-x64-musl' : 'biome-linux-x64',
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

    /**
     * Whether the current PHP environment is using musl libc.
     * This is used to determine the correct Biome.js binary to download.
     */
    private static function isMusl(): bool
    {
        static $isMusl = null;

        if (null !== $isMusl) {
            return $isMusl;
        }

        if (!\function_exists('phpinfo')) {
            return $isMusl = false;
        }

        ob_start();
        phpinfo(\INFO_GENERAL);

        return $isMusl = 1 === preg_match('/--build=.*?-linux-musl/', ob_get_clean() ?: '');
    }
}
