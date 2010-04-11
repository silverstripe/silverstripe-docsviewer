<div id="home">
	<% control DocumentedModules %>
		<% if First %>
			<div id="left-column">
				<div class="box">
					<h2>$Title $Readme</h2>
					
					$Content
				</div>
			</div>
			
			<div id="right-column">
		<% else %>
				<div class="box">
					<h2>$Title $Readme</h2>
				
					$Content
				</div>
		<% end_if %>
		
	<% end_control %>
			</div>
	
	<% if UndocumentedModules %>	
		<div class='undocumented-modules'>
			<p>Undocumented Modules: $UndocumentedModules</p>
		</div>
	<% end_if %>
</div>