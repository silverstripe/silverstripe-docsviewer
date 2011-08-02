<% if VersionWarning %>
	<% include DocumentationVersion_warning %>
<% end_if %>

<div id="module-home">
	<div id="content-column">
		<% if Content %>
			$Content
		<% else %>
			<h2>$Title</h2>
		<% end_if %>
	</div>

	<div id="sidebar-column">
		<% include DocInThisModule %>
	</div>
</div>