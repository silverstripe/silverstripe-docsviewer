<!DOCTYPE html>

<html>
	<% include DocumentationHead %>	
	
	<div id="masthead" <% if Versions %>class="has_versions"<% end_if %>>
		<div class="wrapper">

			<div class="doc-breadcrumbs">
				<p>
					<a class="menu-toggle"><img src="docsviewer/images/menu.png"></a>
						<a class="breadcrumb" href="$BaseHref">Documentation</a>
						<span>/</span>		
						<a class="breadcrumb current">Search</a> 		
				</p>
			</div>
			
				
			
		</div>
	</div>	
	
	<div class="wrapper">
		<div id="layout" class="clearfix">

				$Layout
				
				<% include DocumentationFooter %>
			
		</div>
	</div>
	

	<% if GoogleAnalyticsCode %>
		<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', '$GoogleAnalyticsCode', 'auto');
			ga('send', 'pageview');
		</script>
	<% end_if %>

	<% include DocumentationEnd %>
</html>
