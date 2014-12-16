<div id="documentation_index" class="box">
	<h1>Documentation Index</h1>

	<div id="page-numbers">
		<span>
			<% loop $AllPages.GroupedBy(FirstLetter) %>
				<a href="#$FirstLetter">$FirstLetter</a>
			<% end_loop %>
		</span>
	</div>

	<% loop $AllPages.GroupedBy(FirstLetter) %>
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