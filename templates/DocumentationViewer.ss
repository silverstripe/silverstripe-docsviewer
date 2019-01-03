<!DOCTYPE html>

<html>
	<% include DocumentationHead %>

	<div id="masthead" <% if Versions %>class="has_versions"<% end_if %>>
		<div class="wrapper">
			<div class="menu-bar">
				<a class="logo" href="https://userhelp.silverstripe.org/"></a>
				<a class="menu-open">
					<%t SilverStripe\\DocsViewer\\Controllers\\DocumentationViewer.MENU "Menu" %>
				</a>
			</div>

			<% if Breadcrumbs.count > 1 %>
				<% include DocumentationBreadcrumbs %>
			<% else_if Page.Title %>
				<h1>$Page.Title</h1>
			<% end_if %>
			<% if Page.Introduction %>
				<div class="introduction">
					<p>$Page.Introduction</p>
				</div>
			<% end_if %>

			<% include DocumentationVersions %>
		</div>
	</div>

	<div class="wrapper">
		<div id="layout" class="clearfix">

			<% include DocumentationSidebar %>

			<div id="content">
				$Layout

				<% include DocumentationFooter %>
			</div>
		</div>
	</div>


	<% include DocumentationGA %>
	<% include DocumentationEnd %>
</html>
