<head>
	<% base_tag %>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
    <% if $CanonicalUrl %>
        <link rel="canonical" href="$CanonicalUrl" />
    <% end_if %>
	<title><% if Title %>$Title &#8211; <% end_if %>$DocumentationTitle</title>
</head>

<body>
