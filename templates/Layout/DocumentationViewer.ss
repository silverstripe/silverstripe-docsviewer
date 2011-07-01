<div id="documentation-page">
	<div id="left-column">
		<% if Content %>
			$Content
		<% else %>
			<% include DocNotFound %>
		<% end_if %>
	</div>

	<% if Content %>
	<div id="right-column">
		<% include DocTableOfContents %>
		<% include DocInThisModule %>
	</div>
	<% end_if %>
</div>