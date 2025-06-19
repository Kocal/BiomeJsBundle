# BiomeJsBundle

[![.github/workflows/ci.yaml](https://github.com/Kocal/BiomeJsBundle/actions/workflows/ci.yaml/badge.svg)](https://github.com/Kocal/BiomeJsBundle/actions/workflows/ci.yaml)
[![Packagist Version](https://img.shields.io/packagist/v/kocal/biome-js-bundle)](https://packagist.org/packages/kocal/biome-js-bundle)

A Symfony Bundle to easily download and use [Biome.js](https://biomejs.dev/) in your Symfony applications,
to lint your front assets without needing Node.js (ex: when using [Symfony AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html)).

---

> [!NOTE]
> This documentation is for version `^2.0`.
> You can check the previous [documentation for `^1.0` here](https://github.com/Kocal/BiomeJsBundle/tree/v1.5.0).


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
