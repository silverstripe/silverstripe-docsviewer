<!DOCTYPE html>

<html>
	<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationHead %>

	<div id="masthead" <% if Versions %>class="has_versions"<% end_if %>>
		<div class="wrapper">
			<div class="menu-bar">
				<a class="logo" href="https://userhelp.silverstripe.org/"></a>
				<a class="menu-open">
					<%t SilverStripe\\DocsViewer\\Controllers\\DocumentationViewer.MENU "Menu" %>
				</a>
			</div>

			<% if Breadcrumbs.count > 1 %>
				<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationBreadcrumbs %>
			<% else_if Page.Title %>
				<h1>$Page.Title</h1>
			<% end_if %>
			<% if Page.Introduction %>
				<div class="introduction">
					<p>$Page.Introduction</p>
				</div>
			<% end_if %>

			<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationVersions %>
		</div>
	</div>

	<div class="wrapper">
		<div id="layout" class="clearfix">

			<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationSidebar %>

			<div id="content">
				$Layout

				<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationFooter %>
			</div>
		</div>
	</div>


	<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationGA %>
	<% include SilverStripe\\DocsViewer\\Controllers\\DocumentationEnd %>
</html>
