<div id="sidebar">
	<div class="box">
		$DocumentationSearchForm
		
		<ul class="nav">
			<li><a href="$DocumentationBaseHref" class="top">Home</a></li>

			<% loop Menu %>
				<% if DefaultEntity %>
					<% loop Children %>
						<li class="$LinkingMode <% if Last %>last<% end_if %>">
							<a href="$Link" class="top">$Title</a>

							<% if LinkingMode == current %>
							<% if Children %>
							<ul class="$FirstLast">
								<% loop Children %>
									<li><a href="$Link" class="$LinkingMode">$Title</a>
										<% if LinkingMode == current %>
										<% if Children %>
											<ul class="$FirstLast">
												<% loop Children %>
													<li><a href="$Link" class="$LinkingMode">$Title</a></li>
												<% end_loop %>
											</ul>
										<% end_if %>
										<% end_if %>
									</li>
								<% end_loop %>
							</ul><% end_if %>
							<% end_if %>
						</li>
					<% end_loop %>
				<% else %>
					<li class="$LinkingMode <% if Last %>last<% end_if %>"><a href="$Link" class="top">$Title <% if IsFolder %><span class="is-folder">&#9658;</span><% end_if %></a>
						<% if LinkingMode == current %>
							<% if Children %>
							<ul class="$FirstLast">
								<% loop Children %>
									<li><a href="$Link" class="$LinkingMode">$Title</a>
										<% if LinkingMode == current %>
										<% if Children %>
											<ul class="$FirstLast">
												<% loop Children %>
													<li><a href="$Link" class="$LinkingMode">$Title</a></li>
												<% end_loop %>
											</ul>
										<% end_if %>
										<% end_if %>
									</li>
								<% end_loop %>
							</ul><% end_if %>
						<% end_if %>
					</li>
				<% end_if %>
			<% end_loop %>
		</ul>
	</div>

	<div class="no-box">
		<ul class="minor-nav">
			<li><a href="{$Link(all)}">Documentation Index</a></li>
		</ul>
	</div>
</div>
