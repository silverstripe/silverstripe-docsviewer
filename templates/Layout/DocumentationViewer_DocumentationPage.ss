<div id="documentation-page" class="box">
	<% if Page.Introduction %>
		<% with Page %>
			<div class="introduction">
				<h1>$Title</h1>

				<% if Introduction %>
					<p>$Introduction</p>
				<% end_if %>
			</div>
		<% end_with %>
	<% end_if %>

	<% include DocumentationVersions %>
	
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