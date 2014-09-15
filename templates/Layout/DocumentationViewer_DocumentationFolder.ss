<div class="box">
	<% if SearchQuery %>
		$SearchResults
	<% else %>
		<% include DocumentationVersions %>

		<% if VersionWarning %>
			<% include DocumentationVersion_warning %>
		<% end_if %>

		<h2>$Title</h2>


		<% include DocumentationTableContents %>

		<% loop Children %>
			<ul>
				<li><a href="$Link">$Title</a></li>
			</ul>
		<% end_loop %>

		<% include DocumentationNextPrevious %>
	<% end_if %>
</div>