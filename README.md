# Doctrine Relations Analyzer

![Packagist Version](https://img.shields.io/packagist/v/huluti/doctrine-relations-analyser)
![Packagist License](https://img.shields.io/packagist/l/huluti/doctrine-relations-analyser)
![Packagist Downloads](https://img.shields.io/packagist/dt/huluti/doctrine-relations-analyser)

## Overview

Doctrine Relations Analyzer is a tool designed to analyze and visualize cascade relationships within Doctrine ORM entities in Symfony projects. It helps developers understand how deletion operations propagate through related entities and assists in identifying potential issues or optimizations in data management strategies.

> [!NOTE]
>**Why is it important?**
>
>Managing cascade operations, especially deletions, is crucial to avoid unintentional data loss. This tool provides a human-readable summary to help >developers review and understand these relationships, ensuring they are intentional and correctly implemented.

> [!WARNING]
> This project is a work in progress. Contributions and feedback are welcome.

## Compatibility

- Tested with Doctrine ORM 3 and Symfony 7+.

## Usage

### Requirements

- Symfony >= 6.4
- PHP >= 8.1
- Graphviz:
    - **For Alpine Linux:** `graphviz fontconfig ttf-freefont`

### Installation

#### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
composer require --dev huluti/doctrine-relations-analyser
```

#### Applications that don't use Symfony Flex

##### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
composer require --dev huluti/doctrine-relations-analyser
```

##### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    DoctrineRelationsAnalyserBundle\DoctrineRelationsAnalyserBundle::class => ['dev' => true, 'test' => true],
];
```

### Usage

    php bin/console doctrine-relations-analyser:analyse

#### Examples

To check deletion relations of two entities in a graph:

    php bin/console doctrine-relations-analyser:analyse -o data/ -g --entities="App\\Entity\\User,App\\Entity\\Workspace" -m="deletions"

#### Command-line Arguments

- --entities: Optional. Comma-separated list of entities to analyze
- -m, --mode: Optional. Analysis mode (all, deletions) [default: "all"]
- -o, --output: Optional. Output path for reports generated
- -g, --graph: Optional. Generate Graphviz graph
- --graph-format: Optional. Graph image format (png, svg) [default: "png"]
- -V, --version: Optional. Display help for the given command. When no command is given display help for the list command
- -h, --help: Optional. Display this application version

## Limitations

- Only work with first joinColumn for now.

## Contributions

Contributions are welcome! If you have ideas for improvements, new features, or bug fixes, please open an issue or a pull request.

## Interesting readings

- [How to delete… not to delete yourself?](https://accesto.com/blog/how-to-delete-to-not-delete-yourself)

## Donations

Do you like the tool? Would you like to support its development? Feel free to donate 🤗

[![Liberapay receiving](https://img.shields.io/liberapay/receives/hugoposnic)](https://liberapay.com/hugoposnic)
[![Liberapay patrons](https://img.shields.io/liberapay/patrons/hugoposnic)](https://liberapay.com/hugoposnic)

## License

This project is licensed under the GNU GPL v3 License.
