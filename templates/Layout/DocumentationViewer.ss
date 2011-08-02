<% if VersionWarning %>
	<% include DocumentationVersion_warning %>
<% end_if %>

<div id="documentation-page">
	<div id="content-column">
		$Content
	</div>

	<% if Content %>
	<div id="sidebar-column">
		<% include DocTableOfContents %>
		<% include DocInThisModule %>
	</div>
	<% end_if %>
</div>