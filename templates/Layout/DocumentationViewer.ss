<% if VersionWarning %>
	<% include DocumentationVersion_warning %>
<% end_if %>

<div id="documentation-page">
	<div id="content-column">
		<% if Breadcrumbs %>
			<% include DocumentationBreadcrumbs %>
		<% end_if %>
		
		$Content

		<% if EditLink %>
			<% include DocumentationEditLink %>
		<% end_if %>
	</div>
</div>

<% include DocumentationComments %>