<div id="documentation-page" class="box">
	<% if VersionWarning %>
		<% include DocumentationVersion_warning %>
	<% end_if %>

	<% if Breadcrumbs %>
		<% include DocumentationBreadcrumbs %>
	<% end_if %>

	<% include DocumentationTableContents %>
		
	$Content

	<% include DocumentationNextPrevious %>

	<% if EditLink %>
		<% include DocumentationEditLink %>
	<% end_if %>


	<% include DocumentationComments %>
</div>