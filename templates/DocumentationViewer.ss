<!DOCTYPE html>

<html>
	<head>
		<% base_tag %>
		<meta charset="utf-8" />
		<title><% if Title %>$Title &#8211; <% end_if %>$DocumentationTitle</title>
	</head>
	
	<body>
		<div class="wrapper">
			<div id="layout" class="clearfix">
				<% include DocumentationSidebar %>

				<div id="content">
					$Layout
					
					<% include DocumentationFooter %>
				</div>
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
	</body>
</html>
