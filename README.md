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

- Graphviz:
    - **For Alpine Linux:** `graphviz fontconfig ttf-freefont`

### Installation

    composer require --dev huluti/doctrine-relations-analyser

### Usage

    php bin/console doctrine-relations-analyser:analyse

#### Examples

To check deletion relations of two entities in a graph:

    php bin/console doctrine-relations-analyser:analyse --output data/ --graph --entities="App\\Entity\\User,App\\Entity\\Workspace" --mode="deletions"

#### Command-line Arguments

- --entities: Optional. Comma-separated list of entities to analyze.
- --mode: Optional. Permit to choose the analysis mode (all | deletions).
- --output: Optional. Path for data reporting.
- --graph: Optional. Generate and save visualization graph.

## Limitations

- Only work with first joinColumn for now.

## Contributions

Contributions are welcome! If you have ideas for improvements, new features, or bug fixes, please open an issue or a pull request.

## Donations

Do you like the tool? Would you like to support its development? Feel free to donate 🤗

[![Liberapay receiving](https://img.shields.io/liberapay/receives/hugoposnic)](https://liberapay.com/hugoposnic)
[![Liberapay patrons](https://img.shields.io/liberapay/patrons/hugoposnic)](https://liberapay.com/hugoposnic)

## License

This project is licensed under the GNU GPL v3 License.
