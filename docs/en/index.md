# Sapphire Documentation Module

This module has been developed to read and display content from markdown files in webbrowser. It is an easy
way to bundle end user documentation within a SilverStripe installation.

See [Writing Documentation](dev/docs/en/sapphiredocs/writing-documentation) for more information on how to write markdown files which
are available here. 

To include your docs file here create a __docs/en/index.md__ file. You can also include custom paths and versions. To configure the documentation system the configuration information is available on the [Configurations](dev/docs/en/sapphiredocs/configuration-options)
page.

## Setup

By default, the documentation is available in `dev/docs`. If you want it to live on the webroot instead of a subfolder,
add the following configuration to your `mysite/_config.php`:

	DocumentationViewer::set_link_base('');
	Director::addRules(1, array(
		'$Action' => 'DocumentationViewer',
		'' => 'DocumentationViewer'
	));