<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title>$Title</title>
		<link>$Link</link>

		<opensearch:totalResults>$TotalResults</opensearch:totalResults>
		<opensearch:startIndex>$StartResult</opensearch:startIndex>
		<opensearch:itemsPerPage>$PageLength</opensearch:itemsPerPage>
		
		<atom:link rel="search" type="application/opensearchdescription+xml" href="{$BaseHref}DocumentationSearch/opensearch"/>

		<opensearch:Query role="request" searchTerms="$Query" startPage="1" />

		<% control Results %>
			<item>
				<title>$Title</title>
				<link>$Link</link>
				<description>$Content.LimitCharacters(200)</description>
			</item>
		<% end_control %>
	</channel>
</rss>