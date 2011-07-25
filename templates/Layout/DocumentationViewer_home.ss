<div id="home">
	<h2><% _t('DOCUMENTEDMODULES', 'Documented Modules') %></h2>
	
	<% if Entities %>
		<% control Entities %>
			<div class="module">
				<h3><a href="$Link">$Title</a></h3>
			</div>
		<% end_control %>
	<% else %>
		<p><% _t('NOMODULEDOCUMENTATION', 'No modules with documentation installed could be found.') %></p>
	<% end_if %>
</div>