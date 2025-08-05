<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

use Kocal\BiomeJsBundle\DependencyInjection\BiomeJsExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class KocalBiomeJsBundle extends Bundle
{
    protected function createContainerExtension(): ExtensionInterface
    {
        return new BiomeJsExtension();
    }
}
