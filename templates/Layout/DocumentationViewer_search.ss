<% if AdvancedSearchEnabled %>
	<div class="well">
		$AdvancedSearchForm
	</div>
<% end_if %>


<% if Results %>
	<p class="intro">Your search for <strong>&quot;$SearchQuery.XML&quot;</strong> found $TotalResults result<% if TotalResults != 1 %>s<% end_if %>. Showing page $ThisPage of $TotalPages</p>

	<% loop Results %>
		<div class="result">
			<h2><a href="$Link">$Title</a></h2>
			<p><small>$BreadcrumbTitle</small></p>
			<p>$Content.LimitCharacters(200)</p>
		</div>
	<% end_loop %>
	
	<% if SearchPages %>
		<ul class="pagination">
			<% if PrevUrl = false %><% else %>
				<li class="prev"><a href="$PrevUrl">Prev</a></li>
			<% end_if %>               
			
			<% loop SearchPages %>
				<% if IsEllipsis %>
					<li class="ellipsis">...</li>
				<% else %>
					<% if Current %>
						<li class="active"><strong>$PageNumber</strong></li>
					<% else %>
						<li><a href="$Link">$PageNumber</a></li>
					<% end_if %>
				<% end_if %>
			<% end_loop %>
			
			<% if NextUrl = false %>
			<% else %>
				<li class="next"><a href="$NextUrl">Next</a></li>
			<% end_if %>               
		</ul>         
	<% end_if %>
	
<% else %>
	<p>No Results</p>
<% end_if %>