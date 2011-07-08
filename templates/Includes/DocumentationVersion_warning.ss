<div class="warningBox" id="outdated-release">
	<div class="warningBoxTop">
		<% control VersionWarning %>
			<% if OutdatedRelease %>
				<p>This document contains information for an <strong>outdated</strong> version <% if Top.Version %>(<strong>$Top.Version</strong>)<% end_if %> and may not be maintained any more.</p>
				<p>If some of your projects still use this version, consider upgrading as soon as possible.</p>
			<% else_if FutureRelease %>
				<p>This document contains information about a <strong>future</strong> release <% if StableVersion %>and not the current stable version (<strong>$StableVersion</strong>)<% end_if %>.</p>
				<p>Be aware that information on this page may change and API's may not be stable for production use.</p>
			<% end_if %>
		<% end_control %>
	</div>
</div>
