<div id="sidebar">
	<a class="menu-close" href="#">×</a>
	<div class="box">
		<ul class="nav">
			<% if not HasDefaultEntity %>
				<li><a href="$Link" class="top">Home</a></li>
			<% end_if %>

			<% loop Menu %>
				<% if DefaultEntity %>
					<li><a href="$Link" class="top">Home</a></li>

					<% loop Children %>
						<li class="$LinkingMode <% if Last %>last<% end_if %>">
							<a href="$Link" class="top">$Title</a>

							<% if LinkingMode == section || LinkingMode == current %>
							<% if Children %>
							<ul class="$FirstLast">
								<% loop Children %>
									<li><a href="$Link" class="$LinkingMode">$Title</a>
										<% if LinkingMode == section || LinkingMode == current %>
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
						<% if LinkingMode == section || LinkingMode == current %>
							<% if Children %>
							<ul class="$FirstLast">
								<% loop Children %>
									<li><a href="$Link" class="$LinkingMode">$Title</a>
										<% if LinkingMode == section || LinkingMode == current %>
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
			<li><a href="$DocumentationIndexLink">Documentation Index</a></li>
		</ul>
	</div>
</div>
