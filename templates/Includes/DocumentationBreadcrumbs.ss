<div class="doc-breadcrumbs">
	<p>
		<a class="menu-toggle"><img src="docsviewer/images/menu.png"></a>
		<% loop Breadcrumbs %>	
			<% if not First %>		
				<a class="breadcrumb <% if Last %>current<% end_if %>" href="$Link">$Title</a> <% if Last %><% else %><span>/</span><% end_if %>			
			<% end_if %>
		<% end_loop %>

	</p>
</div>