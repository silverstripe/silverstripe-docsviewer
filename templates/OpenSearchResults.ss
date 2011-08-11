<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">
	<title>$Title</title>
	<link>$Link</link>
	<author> 
		<name>$Author</name>
	</author>
	
	<updated>$Now</updated>
	<opensearch:totalResults>$TotalResults</opensearch:totalResults>
	<opensearch:startIndex>$StartResult</opensearch:startIndex>
	<opensearch:itemsPerPage>$PageLength</opensearch:itemsPerPage>
	<opensearch:Query role="request" searchTerms="$Query" startIndex="$StartResult" count="$PageLength"></opensearch:Query>
	<% control Results %>
	<entry>
		<title><% if BreadcrumbTitle %>$BreadcrumbTitle<% else %>$Title<% end_if %></title>
		<link href="$Link">$Link</link>
		<id>urn:uuid:$ID</id>
		<content type="text">$Content.LimitCharacters(200)</content>
	</entry>
	<% end_control %>
</feed>