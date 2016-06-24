# Documentation Viewer Module

[![Build Status](https://secure.travis-ci.org/silverstripe/silverstripe-docsviewer.png?branch=master)](http://travis-ci.org/silverstripe/silverstripe-docsviewer)

## Maintainer Contact

* Will Rossiter (Nickname: willr, wrossiter)
 <will@fullscreen.io>

## Requirements

These are pulled in via Composer.

* SilverStripe 3.1
* [Parsedown](http://parsedown.org/) and Parsedown Extra.

## Summary

Reads markdown files from a given list of folders from your installation and
provides a web interface for viewing the documentation. Ideal for providing
documentation alongside your module or project code.

A variation of this module powers the main SilverStripe developer documentation
and the user help websites.

For more documentation on how to use the module please read /docs/Writing-Documentation.md
(or via this in /dev/docs/docsviewer/Writing-Documentation in your webbrowser)

## Installation

	composer require "silverstripe/docsviewer" "dev-master"

## Usage

After installing the files via composer, rebuild the SilverStripe database..

	sake dev/build

Then start by viewing the documentation at `yoursite.com/dev/docs`.

If something isn't working, you can run the dev task at `yoursite.com/dev/tasks/CheckDocsSourcesTask`
to automatically check for configuration or source file errors.

Out of the box the module will display the documentation files that have been
bundled into any of your installed modules. To configure what is shown in the
documentation viewer see the detailed [documentation](docs/en/configuration.md).

For more information about how to use the module see each of the documentation

	* [Configuration](docs/en/configuration.md)
	* [Markdown Syntax](docs/en/markdown.md)
	* [Syntax Highlighting](docs/en/syntax-highlighting.md)
	* [Publishing Static Files](docs/en/statichtml.md)

## License

See LICENSE
