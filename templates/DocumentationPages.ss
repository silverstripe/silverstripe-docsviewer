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
<% end_if %>