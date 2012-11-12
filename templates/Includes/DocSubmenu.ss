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
