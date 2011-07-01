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
				<h1><a href="$Link"><% _t('SILVERSTRIPEDOCUMENTATION', 'SilverStripe Documentation') %></a></h1>
				
				<div id="language">
				 	$LanguageForm
				</div>
				
				<div id="breadcrumbs">
					<% include DocBreadcrumbs %>
				</div>	
			</div>
			
			<div id="layout">
				<div id="versions-nav">
					<h2>Versions:</h2>
					
					<ul>
						<% control Versions %>
							<% if MajorRelease %>
								<li class="major-release"><a href="$Link" class="$LinkingMode">$Title</a></li>
							<% else %>
								<li class="module-only"><a href="$Link" class="$LinkingMode">$Title</a></li>
							<% end_if %>
						<% end_control %>
					</ul>
				</div>
				<div id="content">
					$Layout
				</div>
			</div>
		</div>
		
		<div id="footer">
			<p>Documentation powered by <a href="http://www.silverstripe.org">SilverStripe</a>.</p>
		</div>
	</body>
</html>