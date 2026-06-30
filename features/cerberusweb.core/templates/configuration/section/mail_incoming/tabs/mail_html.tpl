<form id="frmSetupMailHtml" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="mail_incoming">
	<input type="hidden" name="action" value="saveMailHtmlJson">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div>
				<div class="cerb-ui-header--title-sm">Images</div>
				<div class="cerb-ui-header--subtitle">Remote images in HTML email can set tracking/advertising cookies, record your IP address, view your browser/device details, and estimate your approximate location. Cerb loads external images through a proxy server to protect your team's privacy.</div>
			</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-panel">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">Proxy</div>
				</div>

				<div class="cerb-ui-form">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Timeout</label>
						<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
							<span>Stop loading remote images after</span>
							<input type="text" name="proxy_image_timeout_ms" value="{$params.proxy_image_timeout_ms}" maxlength="4" style="width:4em;">
							<span>milliseconds</span>
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Redirects</label>
						<input type="hidden" name="proxy_image_redirects_disabled" id="proxyImageRedirectsDisabled" value="{$params.proxy_image_redirects_disabled}">
						<div>
							<div class="cerb-ui-switcher" data-cerb-input="proxyImageRedirectsDisabled">
								<button type="button" data-value="0" {if !$params.proxy_image_redirects_disabled}class="cerb-ui-switcher--active"{/if}>Allow</button>
								<button type="button" data-value="1" {if $params.proxy_image_redirects_disabled}class="cerb-ui-switcher--active"{/if}>Deny</button>
							</div>
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Signing Secret</label>
						<input type="text" name="proxy_image_secret" value="{$params.proxy_image_secret}" size="64">
						<div class="cerb-ui-form--help">An optional secret improves the security of the image proxy by requiring signed URLs. This can be any text, but works best when random.</div>
					</div>
				</div>
			</div>

			<div class="cerb-ui-panel">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">Blocklist</div>
				</div>
				<div class="cerb-ui-form--help">Images matching these patterns will <b>always</b> be blocked:</div>
				<textarea name="proxy_image_blocklist" data-editor-lines="20" spellcheck="false">{$params.proxy_image_blocklist}</textarea>
			</div>

			<div class="cerb-ui-panel">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">Allowlist</div>
				</div>
				<div class="cerb-ui-form--help">Images matching these patterns will <b>always</b> be allowed:</div>
				<textarea name="proxy_image_allowlist" data-editor-lines="20" spellcheck="false">{$params.proxy_image_allowlist}</textarea>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div>
				<div class="cerb-ui-header--title-sm">Links</div>
				<div class="cerb-ui-header--subtitle">Links in HTML email can deceive you about their destination to steal personal information (phishing). Cerb shows you the true destination when clicking an external link.</div>
			</div>
		</div>

		<div class="cerb-ui-panel">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Allow</div>
			</div>
			<div class="cerb-ui-form--help">Links matching these patterns will <b>not</b> display a warning:</div>
			<textarea name="links_whitelist" data-editor-lines="20" spellcheck="false">{$params.links_whitelist}</textarea>
		</div>
	</div>

	<div>
		<button type="button" id="btnSaveMailHtml" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		const $frm = $('#frmSetupMailHtml');

		Devblocks.formDisableSubmit($frm);

		// Pattern lists (one per line; not Twig) — plain ScriptingEditor.
		if(window.CerbUI && CerbUI.ScriptingEditor)
			$frm.find('textarea[name=proxy_image_blocklist], textarea[name=proxy_image_allowlist], textarea[name=links_whitelist]').each(function() { new CerbUI.ScriptingEditor(this); });

		// Each segmented switcher mirrors its choice into the hidden input that carries the POST value
		if(window.CerbUI && CerbUI.Switcher) {
			$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				const input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}

		$frm.find('#btnSaveMailHtml').on('click', function(e) {
			e.stopPropagation();
			Devblocks.saveAjaxForm($frm);
		});
	});
</script>
