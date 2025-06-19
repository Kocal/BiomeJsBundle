# BiomeJsBundle

[![.github/workflows/ci.yaml](https://github.com/Kocal/BiomeJsBundle/actions/workflows/ci.yaml/badge.svg)](https://github.com/Kocal/BiomeJsBundle/actions/workflows/ci.yaml)
![Packagist Version](https://img.shields.io/packagist/v/kocal/biome-js-bundle)

This bundle makes it easy to use [Biome.js](https://biomejs.dev/) in your Symfony project,
to lint and format your assets files without Node.js
(ex: when using Symfony's [AssetMapper Component](https://symfony.com/doc/current/frontend/asset_mapper.html)).

## Installation

Install the bundle with Composer:

```shell
composer require kocal/biome-js-bundle --dev
```

If you use [Symfony Flex](https://symfony.com/doc/current/setup/flex.html), everything must be configured automatically.
If that's not the case, please follow the next steps:

<details>
<summary>Manual installation steps</summary>

1. Register the bundle in your `config/bundles.php` file:

```php
return [
    // ...
    Kocal\BiomeJsBundle\KocalBiomeJsBundle::class => ['dev' => true],
];
```

2. Create the configuration file `config/packages/kocal_biome_js.yaml`:

```yaml
when@dev:
    kocal_biome_js:
        # The Biome.js CLI version to use, that you can find at https://github.com/biomejs/biome/tags:
        # - for >=2.0.0 versions, the git tag follows the pattern "@biomejs/biome@<binary_version>"
        # - for <2.0.0 versions, the git tag follows the pattern "cli/v<binary_version>"
        binary_version: '2.0.0'
```

3. Create the recommended `biome.json` file at the root of your project:

```json
{
    "files": {
        "includes": [
            "**",
            "!assets/vendor/*",
            "!assets/controllers.json",
            "!public/assets/*",
            "!public/bundles/*",
            "!var/*",
            "!vendor/*",
            "!composer.json",
            "!package.json"
        ]
    }
}
```

In you use Biome.js <2.0.0, you can use the following configuration instead:

```json
{
    "files": {
        "ignore": [
            "assets/vendor/*",
            "assets/controllers.json",
            "public/assets/*",
            "public/bundles/*",
            "var/*",
            "vendor/*",
            "composer.json",
            "package.json"
        ]
    }
}
```

</details>

## Configuration

The bundle is configured in the `config/packages/kocal_biome_js.yaml` file:

```yaml
when@dev:
    kocal_biome_js:
        
        # The Biome.js CLI version to use, that you can find at https://github.com/biomejs/biome/tags:
        # - for >=2.0.0 versions, the git tag follows the pattern "@biomejs/biome@VERSION"
        # - for <2.0.0 versions, the git tag follows the pattern "cli/VERSION"
        binary_version: '2.0.0' # required
```

## Usage

### `biomejs:download`

Download the Biome.js CLI binary for your configured version, and for your platform (Linux, macOS, Windows).

By default, the command will download the binary in the `bin/` directory of your project.

```shell
php bin/console biomejs:download
bin/biome --version

# or, with a custom destination directory
php bin/console biomejs:download path/to/bin
path/to/bin/biome --version
```

### `biomejs:check` (deprecated)

> [!WARNING]  
> **Deprecated since 1.5.0**
>
> In version 2.0.0, the command `biomejs:check` will be removed.
> Instead, run the command `biomejs:download <version>` and use `bin/biome check`.

> [!NOTE]
> This command will **not use the Biome.js CLI binary downloaded through `biomejs:download`**,
> but will instead automatically download another Biome.js CLI binary 1.x.x through the **legacy downloading system**.

Run formatter, linter, and import sorting to the requested files.

```shell
# Shows format and lint errors
php bin/console biomejs:check .

# Shows format and lint errors, and fix them if possible
php bin/console biomejs:check . --write
```

### `biomejs:ci` (deprecated)

> [!WARNING]  
> **Deprecated since 1.5.0**
>
> In version 2.0.0, the command `biomejs:ci` will be removed.
> Instead, run the command `biomejs:download <version>` and use `bin/biome ci`.

> [!NOTE]
> This command will **not use the Biome.js CLI binary downloaded through `biomejs:download`**,
> but will instead automatically download another Biome.js CLI binary 1.x.x through the **legacy downloading system**.

Command to use in CI environments. Run formatter, linter, and import sorting to the requested files.

Files won't be modified, the command is a read-only operation.

```shell
# Shows format and lint errors
php bin/console biomejs:ci .
```
