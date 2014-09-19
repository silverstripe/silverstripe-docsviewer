<div class="box">
	<% if Introduction %>
		<div class="introduction">
			<h1>$Title</h1>

			<% if Introduction %>
				<p>$Introduction</p>
			<% end_if %>
		</div>
	<% else %>
		<h2>$Title</h2>
	<% end_if %>

	<% include DocumentationVersions %>

	<% if VersionWarning %>
		<% include DocumentationVersion_warning %>
	<% end_if %>


	<% include DocumentationTableContents %>

	<% loop Children %>
		<ul>
			<li><a href="$Link">$Title</a></li>
		</ul>
	<% end_loop %>

	<% include DocumentationNextPrevious %>
</div>