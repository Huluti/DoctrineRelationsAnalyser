# Doctrine Relations Analyzer

![Packagist Version](https://img.shields.io/packagist/v/huluti/!%5BPackagist%20Downloads%5D(https%3A%2F%2Fimg.shields.io%2Fpackagist%2F%3Ainterval%2Fhuluti%2Fdoctrine-relations-analyser))
![Packagist License](https://img.shields.io/packagist/l/%20huluti/doctrine-relations-analyser)
![Packagist Downloads](https://img.shields.io/packagist/:interval/huluti/doctrine-relations-analyser)

## Overview

Doctrine Relations Analyzer is a tool designed to analyze and visualize cascade relationships within Doctrine ORM entities in Symfony projects. It helps developers understand how deletion operations propagate through related entities and assists in identifying potential issues or optimizations in data management strategies.

### Why is it important?

Managing cascade operations, especially deletions, is crucial to avoid unintentional data loss. This tool provides a human-readable summary to help developers review and understand these relationships, ensuring they are intentional and correctly implemented. This helps in identifying and mitigating risks associated with cascade deletions.

> [!NOTE]  
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

    php bin/console doctrine-relations-analyser:analyse --output data/ --graph --entities="App\\Entity\\User,App\\Entity\\Workspace" --mode="deletions

#### Command-line Arguments

- --entities: Optional. Comma-separated list of entities to analyze.
- --mode: Optional. Permit to choose the analysis mode (all | deletions).
- --output: Optional. Path for data reporting.
- --graph: Optional. Generate and save visualization graph.

## Limitations

- Only work with first joinColumn for now.

## Contributions

Contributions are welcome! If you have ideas for improvements, new features, or bug fixes, please open an issue or a pull request.

## License

This project is licensed under the GNU GPL v3 License.