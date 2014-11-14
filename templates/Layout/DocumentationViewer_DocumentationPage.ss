<div id="documentation-page" class="box">
	<% if VersionWarning %>
		<% include DocumentationVersion_warning %>
	<% end_if %>

	<% include DocumentationTableContents %>

		
	$Content.RAW

	<% include DocumentationNextPrevious %>

	<% if EditLink %>
		<% include DocumentationEditLink %>
	<% end_if %>


	<% include DocumentationComments %>
</div>