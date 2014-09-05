<div id="module-home" class="box">
	<% if VersionWarning %>
		<% include DocumentationVersion_warning %>
	<% end_if %>

	<% if Content %>
		$Content

		<% if EditLink %>
			<% include DocumentationEditLink %>
		<% end_if %>
	<% else %>
		<h2>$Title</h2>
	<% end_if %>
</div>