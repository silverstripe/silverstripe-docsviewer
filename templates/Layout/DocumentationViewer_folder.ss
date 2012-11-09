<% if VersionWarning %>
	<% include DocumentationVersion_warning %>
<% end_if %>

<div id="module-home">
	<div id="content-column">
		<% if Content %>
			<% if Breadcrumbs %>
				<% include DocBreadcrumbs %>
			<% end_if %>
			$Content

			<% if EditLink %>
				<div id="edit-link">
					<p><a target="_blank" href="$EditLink">Edit this page</a></p>
				</div>
			<% end_if %>
		<% else %>
			<h2>$Title</h2>
		<% end_if %>
	</div>

	<div id="sidebar-column">
		<% include DocInThisModule %>
	</div>
</div>