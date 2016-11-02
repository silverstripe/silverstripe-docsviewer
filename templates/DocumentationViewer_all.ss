<!DOCTYPE html>

<html>
	<% include DocumentationHead %>

	<div id="masthead" <% if Versions %>class="has_versions"<% end_if %>>
		<div class="wrapper">

			<div class="doc-breadcrumbs">
				<p>
					<a class="breadcrumb" href="$BaseHref">Documentation</a>
					<span>/</span>
					<a class="breadcrumb current">Index</a>
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


	<% include DocumentationGA %>
	<% include DocumentationEnd %>
</html>
