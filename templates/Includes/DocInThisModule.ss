<% if EntityPages %>
	<div id="sibling-pages" class="sidebar-box">
		<h4>In this module:</h4>
		<ul>
			<% loop EntityPages %>
				<li>
					<a href="$Link" class="$LinkingMode">$Title</a>
					<% if Top.SubmenuLocation = nested %>
						<% if Children %>
							<% include DocSubmenu %>
						<% end_if %>
					<% end_if %>
				</li>
			<% end_loop %>
		</ul>
	</div>
<% end_if %>

<% if SubmenuLocation = separate %>
	<% loop CurrentLevelOnePage %>
		<% if Children %>
			<div class = "sidebar-box">
				<h4>$title</h4>
				<% include DocSubmenu %>
			</div>
		<% end_if %>
	<% end_loop %>
<% end_if %>
