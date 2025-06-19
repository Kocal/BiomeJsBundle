<?php

declare(strict_types=1);

namespace Kocal\BiomeJsBundle;

/**
 * @internal
 */
final class BiomeJsBinary
{
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
