<div class="doc-breadcrumbs">
	<p>
		<% loop Breadcrumbs %>	
			<% if not First %>
				<a class="breadcrumb <% if Last %>current<% end_if %>" href="$Link">$Title</a> <% if Last %><% else %><span>/</span><% end_if %>
			<% end_if %>
		<% end_loop %>

	</p>
</div>
