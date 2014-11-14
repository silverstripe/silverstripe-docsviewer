<div class="box">
	<% if Introduction %>
		<div class="introduction">
			<h1>$Title</h1>

			<% if Introduction %>
				<p>$Introduction</p>
			<% end_if %>
		</div>

		<% if Breadcrumbs %>
			<% include DocumentationBreadcrumbs %>
		<% end_if %>
	<% else %>
		<% if Breadcrumbs %>
			<% include DocumentationBreadcrumbs %>
		<% end_if %>

		<h1>$Title</h1>
	<% end_if %>

	<% if VersionWarning %>
		<% include DocumentationVersion_warning %>
	<% end_if %>

	<% include DocumentationTableContents %>

	<% if Children %>
		<div class="documentation_children">
			<ul>
				<% loop Children %>
					<li>
						<h3><a href="$Link">$Title</a></h3>
						<% if Summary %><p>$Summary</p><% end_if %>
					</li>
				<% end_loop %>
			</ul>
		</div>
	<% else %>
		<div class="documentation_children">
			<ul>
				<% loop Menu %>
					<li>
						<h3><a href="$Link">$Title</a></h3>
						<% if Summary %><p>$Summary</p><% end_if %>
					</li>
				<% end_loop %>
			</ul>
		</div>
	<% end_if %>

	<% include DocumentationNextPrevious %>
</div>