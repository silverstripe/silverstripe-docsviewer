<div id="sidebar" class="box">
	<ul class="nav">
	<% loop Entities %>
		<% if DefaultEntity %>
	
		<% else %>
			<li><a href="$Link" class="$LinkingMode top">$Title <% if IsFolder %><span class="is-folder">&#9658;</span><% end_if %></a>
				<% if LinkingMode == current %>
					<% if Children %>
					<ul>
						<% loop Children %>
							<li><a href="$Link" class="$LinkingMode">$Title</a></li>
						<% end_loop %>
					</ul><% end_if %>
				<% end_if %>
			</li>
		<% end_if %>
	<% end_loop %>
	</ul>
</div>
