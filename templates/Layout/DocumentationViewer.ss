<div id="documentation-page">
	<div id="left-column">
		<% if Content %>
			$Content
		<% else %>
			<p>Woops page not found</p>
		<% end_if %>
	</div>

	<div id="right-column">
		<% include DocTableOfContents %>
		<% include DocInThisModule %>
	</div>
</div>