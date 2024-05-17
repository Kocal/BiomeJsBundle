# BiomeJsBundle

[![.github/workflows/ci.yaml](https://github.com/Kocal/BiomeJsBundle/actions/workflows/ci.yaml/badge.svg)](https://github.com/Kocal/BiomeJsBundle/actions/workflows/ci.yaml)

This bundle makes it easy to use [Biome.js](https://biomejs.dev/) in your Symfony project,
to lint and format your assets files without Node.js
(ex: when using Symfony's [AssetMapper Component](https://symfony.com/doc/current/frontend/asset_mapper.html)).

## Installation

Install the bundle with Composer:

```bash
composer require kocal/biome-js-bundle --dev
```

The bundle should have been automatically enabled in your Symfony application (`config/bundles.php`). 
If that's not the case, you can enable it manually:

```php
// config/bundles.php
return [
    // ...
    Kocal\BiomeJsBundle\KocalBiomeJsBundle::class => ['all' => true],
];
```

## Configuration

If you want to use a specific version of Biome.js, you can configure it in your `config/packages/kocal_biome_js.yaml`:

```yaml
kocal_biome_js:
    version: v1.7.3
```

To [configure Biome.js it-self](https://biomejs.dev/reference/configuration), you must create a `biome.json` file at the root of your project.

A recommended configuration for Symfony projects is to ignore files from `assets/vendor/`, `vendor/` and `public/bundles/`:
```json
{
  "files": {
    "ignore": [
      "assets/vendor/*",
      "vendor/*",
      "public/bundles/*"
    ]
  }
}
```

## Usage

The latest Biome.js CLI binary is automatically installed (if not already installed) when running one of the `biome:*` command.

### `biome:check`

Runs formatter, linter and import sorting to the requested files.

```bash
# Shows format and lint errors
php bin/console biome:check .

# Shows format and lint errors, and fix them if possible
php bin/console biome:check . --apply
```

### `biome:ci`

Command to use in CI environments. Runs formatter, linter and import sorting to the requested files.

Files won't be modified, the command is a read-only operation.

```bash
# Shows format and lint errors
php bin/console biome:ci .
