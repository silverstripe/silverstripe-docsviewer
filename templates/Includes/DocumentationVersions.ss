<% if Versions %>
	<div class="versions">
		<p>Versions:</p>
		
		<ul>
			<% loop Versions %>
				<li><a href="$Link" class="$LinkingMode">$Title</a></li>
			<% end_loop %>
		</ul>
	</div>
<% end_if %>