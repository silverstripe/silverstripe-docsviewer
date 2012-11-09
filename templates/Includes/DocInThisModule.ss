<% if EntityPages %>
	<div id="sibling-pages" class="sidebar-box">
		<h4>In this module:</h4>
		<ul>
			<% control EntityPages %>
				<li>
					<a href="$Link" class="$LinkingMode">$Title</a>
					<% if Children %>
						<ul id="submenu">
							<% control Children %>
								<li><a href="$Link" class="$LinkingMode">
									$Title <% if IsFolder %><span class="is-folder">&#9658;</span><% end_if %>
								    </a>
								<% if Children %>
								<ul>
									<% control Children %>
									<li><a href="$Link" class="$LinkingMode">$Title</a></li>
									<% end_control %>
								</ul><% end_if %>
								</li>
							<% end_control %>
						</ul>
					<% end_if %>
				</li>
			<% end_control %>
		</ul>
	</div>
<% end_if %>