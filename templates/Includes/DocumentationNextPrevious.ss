<% if NextPage || PreviousPage %>
	<div class="next-prev">
		<% if PreviousPage %>
			<p class="prev-link"><a href="$PreviousPage.Link">$PreviousPage.Title</a></p>
		<% end_if %>

		<% if NextPage %>
			<p class="next-link"><a href="$NextPage.Link">$NextPage.Title</a></p>
		<% end_if %>
	</div>
<% end_if %>