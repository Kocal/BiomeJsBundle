<?php
declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class FunctionalTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $fs = new Filesystem();
        if (is_dir($biomejsVarDir = __DIR__.'/fixtures/var/biomejs')) {
            $fs->remove($biomejsVarDir);
        }
    }

    public function testCommandCheck(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('biomejs:check');
        $commandTester = new CommandTester($command);

        $statusCode = $commandTester->execute([
            'path' => [__DIR__.'/fixtures/'],
        ]);
        self::assertSame(Command::FAILURE, $statusCode, 'The command should return a non-zero exit code');

        $output = $commandTester->getDisplay(true);
        self::assertStringContainsString('Biome.js check failed: see output above.', $output);
        // Linter
        self::assertStringContainsString('BiomeJsBundle/tests/fixtures/say-hello.ts:3:17 lint/style/useTemplate', $output);
        self::assertStringContainsString('Template literals are preferred over string concatenation.', $output);
        // Formatter
        self::assertStringContainsString('BiomeJsBundle/tests/fixtures/bootstrap.js format', $output);
        self::assertStringContainsString('Formatter would have printed the following content:', $output);
    }

    public function testCommandCi(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('biomejs:ci');
        $commandTester = new CommandTester($command);

        $statusCode = $commandTester->execute([
            'path' => [__DIR__.'/fixtures/'],
        ]);
        self::assertSame(Command::FAILURE, $statusCode, 'The command should return a non-zero exit code');

        $output = $commandTester->getDisplay(true);
        self::assertStringContainsString('Biome.js ci failed: see output above.', $output);
        // Linter
        self::assertStringContainsString('BiomeJsBundle/tests/fixtures/say-hello.ts:3:17 lint/style/useTemplate', $output);
        self::assertStringContainsString('Template literals are preferred over string concatenation.', $output);
        // Formatter
        self::assertStringContainsString('BiomeJsBundle/tests/fixtures/bootstrap.js format', $output);
        self::assertStringContainsString('File content differs from formatting output', $output);
    }
}