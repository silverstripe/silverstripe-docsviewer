<div class="warningBox" id="outdated-release">
	<div class="warningBoxTop">
		<% loop VersionWarning %>
			<% if OutdatedRelease %>
				<p>This document contains information for an <strong>outdated</strong> version <% if $Version %>(<strong>$Version</strong>)<% end_if %> and may not be maintained any more. If some of your projects still use this version, consider upgrading as soon as possible.</p>
			<% else_if FutureRelease %>
				<p>This document contains information about a <strong>future</strong> release <% if $VersionWarning.StableVersion %>and not the current stable version (<strong>$VersionWarning.StableVersion</strong>)<% end_if %>. Be aware that information on this page may change and API's may not be stable for production use.</p>
			<% end_if %>
		<% end_loop %>
	</div>
</div>
