<div id="documentation-page">
	<div id="left-column">
		$Content
	</div>

	<% if Content %>
	<div id="right-column">
		<% include DocTableOfContents %>
		<% include DocInThisModule %>
	</div>
	<% end_if %>
</div>