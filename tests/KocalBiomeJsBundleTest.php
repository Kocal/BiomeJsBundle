<?php
declare(strict_types=1);

namespace Kocal\BiomeJsBundle\Tests;

use Kocal\BiomeJsBundle\DependencyInjection\BiomeJsExtension;
use Kocal\BiomeJsBundle\KocalBiomeJsBundle;
use PHPUnit\Framework\TestCase;

final class KocalBiomeJsBundleTest extends TestCase
{
    public function testContainerExtension(): void
    {
        $bundle = new KocalBiomeJsBundle();

        self::assertInstanceOf(BiomeJsExtension::class, $bundle->getContainerExtension());
    }
}