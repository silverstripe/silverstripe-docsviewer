<% if NextPage || PreviousPage %>
	<div class="next-prev clearfix">
		<% if PreviousPage %>
			<p class="prev-link"><a class="btn" href="$PreviousPage.Link">&laquo; $PreviousPage.Title</a></p>
		<% end_if %>

		<% if NextPage %>
			<p class="next-link"><a class="btn" href="$NextPage.Link">$NextPage.Title &raquo;</a></p>
		<% end_if %>
	</div>
<% end_if %>