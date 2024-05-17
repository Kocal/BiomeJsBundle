<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Tests;

use Kocal\BiomeJsBundle\BiomeJsBinary;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BiomeJsBinaryTest extends TestCase
{
    public function testBinaryIsDownloadedIfNotExists(): void
    {
        $binaryDownloadDir = __DIR__ . '/fixtures/var/download';
        $fs = new Filesystem();
        if (file_exists($binaryDownloadDir)) {
            $fs->remove($binaryDownloadDir);
        }
        $fs->mkdir($binaryDownloadDir);

        $client = new MockHttpClient([
            new MockResponse('fake binary contents'),
        ]);

        $binary = new BiomeJsBinary(
            __DIR__,
            $binaryDownloadDir,
            'fake-version',
            $client
        );
        $process = $binary->createProcess(['check', '--apply', '*.{js,ts}']);
        $this->assertFileExists($binaryDownloadDir . '/fake-version/' . BiomeJsBinary::getBinaryName());

        // Windows doesn't wrap arguments in quotes
        $expectedTemplate = '\\' === \DIRECTORY_SEPARATOR ? '"%s" check --apply *.{js,ts}' : "'%s' 'check' '--apply' '*.{js,ts}'";

        $this->assertSame(
            sprintf($expectedTemplate, $binaryDownloadDir . '/fake-version/' . BiomeJsBinary::getBinaryName()),
            $process->getCommandLine()
        );
    }
}
