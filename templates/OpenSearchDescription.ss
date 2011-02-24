<?xml version="1.0" encoding="UTF-8"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">

	<% if ShortName %><ShortName>$ShortName</ShortName><% end_if %>
	<% if Description %><Description>$Description</Description><% end_if %>
	<% if Tags %><Tags>$Tags</Tags><% end_if %>
	<% if Contact %><Contact>$Content</Contact><% end_if %>
  	
	<% if SearchPageLink %><Url type="text/html" template="$SearchPageLink"></Url><% end_if %>
	<% if SearchPageAtom %><Url type="application/atom+xml" template="$SearchPageAtom"></Url><% end_if %>
	<% if SearchPageJson %><Url type="application/x-suggestions+json" template="$SearchPageJson"></Url><% end_if %>
</OpenSearchDescription>