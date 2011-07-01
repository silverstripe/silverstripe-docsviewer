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
			</div>
			
			<div id="layout">
				<div id="search-bar">					
					<div id="breadcrumbs">
						<% include DocBreadcrumbs %>
					</div>

					<div id="search">
						$DocumentationSearchForm
					</div>

					<% if Entities %>
					<div id="entities-nav" class="documentation-nav">
						<h2>Modules:</h2>
							<ul>
							<% control Entities %>
								<li><a href="$Link" class="$LinkingMode">$Title</a></li>
							<% end_control %>
						</ul>
					</div>
					<% end_if %>
										
					<% if Versions %>
					<div id="versions-nav" class="documentation-nav">
						<h2>Versions:</h2>
							<ul>
							<% control Versions %>
								<li><a href="$Link" class="$LinkingMode">$Title</a></li>
							<% end_control %>
						</ul>
					</div>
					<% end_if %>
				</div>
				
				<div id="content" class="typography">
					$Layout
				</div>
			</div>
		</div>
		
		<% include DocumentationFooter %>
	</body>
</html>