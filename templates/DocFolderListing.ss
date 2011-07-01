<div id="folder-listing">
	<h2>$Title</h2>
	
	<% if Pages %>
		<ul>
			<% control Pages %>
				<li><a href="$Link">$Title</a></li>
			<% end_control %>
		</ul>
	<% else %>
		<p>No documentation pages found for $Title. If you need help writing documentation please consult the README.</p>
	<% end_if %>
</div>