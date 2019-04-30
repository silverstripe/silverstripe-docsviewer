<div id="documentation-page" class="box">
	<% if VersionWarning %>
		<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationVersion_warning Version=$Entity.Version %>
	<% end_if %>

	<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationTableContents %>

		
	$Content.RAW

	<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationNextPrevious %>

	<% if EditLink %>
		<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationEditLink %>
	<% end_if %>


	<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationComments %>
</div>