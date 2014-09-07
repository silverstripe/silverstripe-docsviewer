<div id="sidebar">
	<div class="box">
		<ul class="nav">
		<% loop Entities %>
			<% if DefaultEntity %>
		
			<% else %>
				<li class="$LinkingMode"><a href="$Link" class="top">$Title <% if IsFolder %><span class="is-folder">&#9658;</span><% end_if %></a>
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

	<div class="no-box">
		<ul class="minor-nav">
			<li><a href="{$Link(all)}">Documentation Index</a></li>
		</ul>
	</div>
</div>
