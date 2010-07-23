# Writing Documentation #

Your documentation needs to go in the specific modules docs folder which it refers mostly too. For example if you want to document
a feature of your custom module 'MyModule' you need to create markdown files in mymodule/docs/.

The files have to end with the __.md__ extension. The documentation viewer will automatically replace hyphens (-) with spaces (since you cannot
have spaces web / file systems).

Also docs folder should be localized. Even if you do not plan on using multiple languages you should at least write your documentation
in a 'en' subfolder

	/module/docs/en/

## Syntax ##

This uses a customized markdown extra parser. To view the syntax for page formatting check out [Daring Fireball](http://daringfireball.net/projects/markdown/syntax)

## Creating Hierarchy ##

The document viewer supports folder structure. There is a 9 folder limit on depth / number of sub categories you can create. 
Each level deep it will generate the nested urls.
 
## Directory Listing ##

Each folder you create should also contain a __index.md__ file (see sapphiredocs/doc/en/index.md) which contains an overview of the
module and related links.

## Table of Contents ##

The table of contents on each module page is generated 

 