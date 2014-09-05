<div class="doc-breadcrumbs">
<p>
	<% loop Breadcrumbs %>
		<a href="$Link">$Title</a> <% if Last %><% else %>&rsaquo;<% end_if %>
	<% end_loop %>
</p>
</div>