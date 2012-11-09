<% if VersionWarning %>
	<% include DocumentationVersion_warning %>
<% end_if %>

<div id="documentation-page">
	<div id="content-column">
		<% if Breadcrumbs %>
			<% include DocBreadcrumbs %>
		<% end_if %>
		
		$Content

		<% if EditLink %>
			<div id="edit-link">
				<p><a target="_blank" href="$EditLink">Edit this page</a></p>
			</div>
		<% end_if %>
	</div>

	<% if Content %>
	<div id="sidebar-column">
		<% include DocInThisModule %>
	</div>
	<% end_if %>
</div>