<div id="documentation-page">
	<div id="left-column">
		<% if Results %>
			<% control Results %>
				<h2><a href="$Link">$Title</a></h2>
				$Content.Summary
			<% end_control %>
		<% else %>
			<p>No Results</p>
		<% end_if %>
	</div>

	<div id="right-column">

	</div>
</div>