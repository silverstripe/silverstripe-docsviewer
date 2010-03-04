# Writing Documentation #


Your documentation needs to go in the specific modules doc folder which it refers mostly too. For example if you want to document
a feature of your custom module 'MyModule' you need to create markdown files in mymodule/docs/.

The files have to end with the __.md__ extension. The documentation viewer will automatically replace hyphens (-) with spaces (since you cannot
have spaces easily in some file systems).

## Syntax ##
This uses a customized markdown extra parser. To view the syntax for page formatting check out [http://daringfireball.net/projects/markdown/syntax](Daring Fireball)


## Creating Hierarchy ##

The document viewer supports folder structure. There is no limit on depth or number of sub categories you can create.
 
## Customizing Page Order ##

Sometimes you will have pages which you want at the top of the documentation viewer summary. Pages like Getting-Started will come after Advanced-Usage 
due to the default alphabetical ordering.

To handle this you can use a number prefix for example __01-My-First-Folder__ which would be the first folder in the list.

DocumentationViewer will remove the __01-__ from the name as well so you don't need to worry about labels for your folders with numbers. It will be
outputted in the front end as __My First Folder__

 