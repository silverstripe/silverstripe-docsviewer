<div id="sibling-pages" class="sidebar-box">
	<ul>
		<% control EntityPages %>
			<li>
				<a href="$Link" class="$LinkingMode">$Title</a>
				<% if Children %>
					<ul>
						<% control Children %>
							<li><a href="$Link" class="$LinkingMode">$Title</a>
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