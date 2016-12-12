<div id="documentation_index" class="box">
	<div id="page-numbers">
		<span>
			<% loop $AllVersionPages.GroupedBy(FirstLetter) %>
				<a href="#$FirstLetter">$FirstLetter</a>
			<% end_loop %>
		</span>
	</div>

	<% loop $AllVersionPages.GroupedBy(FirstLetter) %>
		<h2 id="$FirstLetter">$FirstLetter</h2>

		<ul class="third semantic">
			<% loop $Children %>
				<li>
					<a href="$Link">$Title</a>
				</li>
			<% end_loop %>
		</ul>
	<% end_loop %>
</div>
