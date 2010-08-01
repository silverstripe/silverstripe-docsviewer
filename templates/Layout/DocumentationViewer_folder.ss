<div id="module-home">

<div id="left-column">
	<% if Content %>
		$Content
	<% else %>
		<h2>$Title</h2>
	<% end_if %>
</div>

	<div id="right-column">
		<% include DocInThisModule %>
	</div>
</div>