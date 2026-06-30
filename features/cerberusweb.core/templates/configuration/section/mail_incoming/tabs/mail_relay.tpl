<form id="frmSetupMailRelay" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="mail_incoming">
<input type="hidden" name="action" value="saveMailRelayJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">External Relay</div>
				<div class="cerb-ui-header--subtitle">The email relay lets workers respond to messages from external mail applications (e.g. Gmail, mobile phones, Outlook) instead of always requiring the Cerb web browser. Relayed responses are received from a worker's personal email address and rewritten so they appear to be from Cerb before being sent to a conversation's participants. This protects the privacy of personal worker email addresses while still providing the benefits of Cerb (shared history, assignments, etc).</div>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Authentication</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--help">
			By default, relayed messages are authenticated by checking the mail headers. Copies of mail that are relayed to workers outside of Cerb using bot behaviors are "signed" with a secret key in the <tt>Message-Id:</tt> header. According to the RFC-2822 standard, this <tt>Message-Id:</tt> should be referenced in the <tt>In-Reply-To:</tt> header of any reply. Unfortunately, some email applications "break the Internet" by ignoring these conventions &mdash; common culprits include Microsoft Exchange and some Android and Blackberry mobile devices. See the <a href="https://cerb.ai/guides/mail/relaying/" target="_blank" rel="noopener">documentation</a> for more information.
		</div>

		<div class="cerb-ui-panel cerb-ui-panel--alert">
			<div class="cerb-ui-header">
				<div class="cerb-ui-callout">
					<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
					<div>
						<div class="cerb-ui-header--title-sm">Be careful when disabling authentication!</div>
						<div class="cerb-ui-header--subtitle">When authentication is disabled, anyone can forge a message <tt>From:</tt> one of your workers and have it relayed to arbitrary conversations. Set up alternative authentication using bots in Mail Filtering to approve or deny inbound worker replies through the relay.</div>
					</div>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Relay functionality</label>
			<input type="hidden" name="relay_disable" id="relayDisable" value="{$relay_disable}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="relayDisable">
					<button type="button" data-value="0" {if empty($relay_disable)}class="cerb-ui-switcher--active"{/if}>{'common.enabled'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="1" {if $relay_disable}class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		<div id="configMailRelayOptions" class="cerb-ui-form" style="{if $relay_disable}display:none;{/if}">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Built-in relay authentication</label>
				<input type="hidden" name="relay_disable_auth" id="relayDisableAuth" value="{$relay_disable_auth}">
				<div>
					<div class="cerb-ui-switcher" data-cerb-input="relayDisableAuth">
						<button type="button" data-value="0" {if empty($relay_disable_auth)}class="cerb-ui-switcher--active"{/if}>{'common.enabled'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="1" {if $relay_disable_auth}class="cerb-ui-switcher--active"{/if}>{'common.disabled'|devblocks_translate|capitalize}</button>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">The 'From:' address on relay messages is</label>
				<input type="hidden" name="relay_spoof_from" id="relaySpoofFrom" value="{$relay_spoof_from}">
				<div>
					<div class="cerb-ui-switcher" data-cerb-input="relaySpoofFrom">
						<button type="button" data-value="0" {if !$relay_spoof_from}class="cerb-ui-switcher--active"{/if}>{$replyto_default->email}</button>
						<button type="button" data-value="1" {if $relay_spoof_from}class="cerb-ui-switcher--active"{/if}>The original sender</button>
					</div>
				</div>
				<div class="cerb-ui-form--help">Using the reply-to address is recommended for the best compatibility with most mail readers; the original sender can still be included as part of the relay template. "Spoofed" senders may be flagged as spam or rejected by workers' mail servers, and may break conversation threading.</div>
			</div>
		</div>
	</div>
</div>

<div>
	<button type="button" id="btnSaveMailRelay" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupMailRelay');

	Devblocks.formDisableSubmit($frm);

	// Each segmented switcher mirrors its choice into the hidden input that carries the POST value
	if(window.CerbUI && CerbUI.Switcher) {
		$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			const input = document.getElementById(this.getAttribute('data-cerb-input'));
			const isRelayToggle = (input && input.id === 'relayDisable');

			new CerbUI.Switcher(this, {
				value: input ? input.value : null,
				onSelect: function(value) {
					if(input) input.value = value;

					// Show the detailed options only when the relay is enabled
					if(isRelayToggle) {
						if('0' == value) {
							$('#configMailRelayOptions').fadeIn();
						} else {
							$('#configMailRelayOptions').fadeOut();
						}
					}
				}
			});
		});
	}

	$frm.find('#btnSaveMailRelay').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxForm($frm);
	});
});
</script>
