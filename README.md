# Documentation Viewer Module

[![Build Status](https://secure.travis-ci.org/silverstripe/silverstripe-docsviewer.png?branch=master)](http://travis-ci.org/silverstripe/silverstripe-docsviewer)

## Maintainer Contact

* Will Rossiter (Nickname: willr, wrossiter) 
 <will@fullscreen.io>

## Requirements

* SilverStripe 3.1

## Summary

Reads text files from a given list of folders from your installation and 
provides a web interface for viewing. 

To read documentation go to yoursite.com/dev/docs/

For more documentation on how to use the module please read /docs/Writing-Documentation.md 
(or via this in /dev/docs/docsviewer/Writing-Documentation in your webbrowser)

**Note** This module assumes you are using numeric values for your versions.

### Static Publisher

If you wish to generate a truly static version of your documentation after it 
has been rendered through the website, add the [Static Publisher](https://github.com/silverstripe-labs/silverstripe-staticpublisher) 
module to your documentation project and set the following configuration in your 
applications config.yml:

```
StaticExporter:
  extensions:
    - DocumentationStaticPublisherExtension
```

If you don't plan on using static publisher for anything else and you have the 
cms module installed, make sure you disable the CMS from being published. 

Again, in your applications config.yml file

```
StaticExporter:
  disable_sitetree_export: true
```