<!DOCTYPE html>

<html>
	<head>
		<% base_tag %>
		<meta charset="utf-8" />
		<title>SilverStripe Documentation</title>
		
		<% require themedCSS(DocumentationViewer) %>
		<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
		<% require javascript(sapphiredocs/javascript/DocumentationViewer.js) %>
		
	</head>
	
	<body>
		<div id="container">
			<div id="header">
				<h1><a href="dev/docs/">SilverStripe Documentation</a></h1>
					
				$Breadcrumbs
			</div>
				
			$Layout
		</div>
	</body>
</html>
