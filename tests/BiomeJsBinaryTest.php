<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Tests;

use Kocal\BiomeJsBundle\BiomeJsBinary;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BiomeJsBinaryTest extends TestCase
{
    private const BINARY_DOWNLOAD_DIR = __DIR__ . '/fixtures/var/download';

    private MockHttpClient $httpClient;

    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        $fs = new Filesystem();
        $fs->remove(self::BINARY_DOWNLOAD_DIR);
        $fs->mkdir(self::BINARY_DOWNLOAD_DIR);

        $this->httpClient = new MockHttpClient(function (string $method, string $url, array $options) {
            // Mock GitHub API, the releases must contain multiples stable versions and nightly versions,
            // and also not-CLI releases (e.g.: js-api).
            if ('GET' === $method && 'https://api.github.com/repos/biomejs/biome/releases' === $url) {
                return new JsonMockResponse(
                    json_decode(
                        file_get_contents(__DIR__ . '/github-biomejs-releases.json') ?: throw new \RuntimeException('Cannot read file "github-biomejs-releases.json".'),
                        associative: true,
                        flags: JSON_THROW_ON_ERROR
                    )
                );
            }

            if ('GET' === $method && str_starts_with($url, 'https://github.com/biomejs/biome/releases/download/cli')) {
                $binaryName = BiomeJsBinary::getBinaryName();

                return match ($url) {
                    'https://github.com/biomejs/biome/releases/download/cli/v1.8.1/' . $binaryName => new MockResponse('fake-binary-content v1.8.1'),
                    'https://github.com/biomejs/biome/releases/download/cli/v1.8.3/' . $binaryName => new MockResponse('fake-binary-content v1.8.3'),
                    'https://github.com/biomejs/biome/releases/download/cli/v1.8.4-nightly.bd1d0c6/' . $binaryName => new MockResponse('fake-binary-content v1.8.4-nightly.bd1d0c6'),
                    default => new MockResponse('Not Found', ['http_code' => 404]),
                };
            }

            return new MockResponse('Not Found', ['http_code' => 404]);
        });

        $this->cache = new ArrayAdapter();
    }

    /**
     * @return iterable<array{passedVersion: ?string, expectedVersion: string}>
     */
    public static function provideBinaryIsDownloadedIfNotExists(): iterable
    {
        yield 'specific version' => ['passedVersion' => 'v1.8.1', 'expectedVersion' => 'v1.8.1'];
        yield 'latest stable version' => ['passedVersion' => null, 'expectedVersion' => 'v1.8.3'];
        yield 'latest stable version (explicit)' => ['passedVersion' => 'latest_stable', 'expectedVersion' => 'v1.8.3'];
        yield 'latest nightly version' => ['passedVersion' => 'latest_nightly', 'expectedVersion' => 'v1.8.4-nightly.bd1d0c6'];
    }

    #[DataProvider('provideBinaryIsDownloadedIfNotExists')]
    public function testBinaryIsDownloadedIfNotExists(?string $passedVersion, string $expectedVersion): void
    {
        $binary = new BiomeJsBinary(
            __DIR__,
            self::BINARY_DOWNLOAD_DIR,
            $passedVersion,
            $this->cache,
            $this->httpClient,
        );
        $process = $binary->createProcess(['check', '--apply', '*.{js,ts}']);
        self::assertFileExists(self::BINARY_DOWNLOAD_DIR . '/' . $expectedVersion . '/' . BiomeJsBinary::getBinaryName());

        // Windows doesn't wrap arguments in quotes
        $expectedTemplate = '\\' === \DIRECTORY_SEPARATOR ? '"%s" check --apply *.{js,ts}' : "'%s' 'check' '--apply' '*.{js,ts}'";

        self::assertSame(
            sprintf($expectedTemplate, self::BINARY_DOWNLOAD_DIR . '/' . $expectedVersion . '/' . BiomeJsBinary::getBinaryName()),
            $process->getCommandLine()
        );

        $cacheValues = $this->cache->getValues();
        if (null !== $passedVersion && str_starts_with($passedVersion, 'v')) {
            // A specific version was passed, so no HTTP call expected and no cache call
            self::assertCount(0, $cacheValues);
        } else {
            // No specific version was passed, so an HTTP call expected and cache should be set
            self::assertCount(1, $cacheValues);
            self::assertSame($expectedVersion, unserialize($cacheValues[array_key_first($cacheValues)]));

            // Check that the binary is not downloaded again, but the cache is used
            $binary = $this->cloneAndResetBinary($binary);
            $this->httpClient->setResponseFactory(fn () => throw new \LogicException('No HTTP request should be made'));
            $binary->createProcess(['check', '--apply', '*.{js,ts}']);

            $cacheValues = $this->cache->getValues();
            self::assertCount(1, $cacheValues);
            self::assertSame($expectedVersion, unserialize($cacheValues[array_key_first($cacheValues)]));
        }
    }

    private function cloneAndResetBinary(BiomeJsBinary $binary): BiomeJsBinary
    {
        $binary = clone $binary;

        $reflProperty = new \ReflectionProperty($binary, 'cachedVersion');
        $reflProperty->setValue($binary, null);

        return clone $binary;
    }
}
