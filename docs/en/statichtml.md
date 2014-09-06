title: Publishing Static Files

# HTML Publishing

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