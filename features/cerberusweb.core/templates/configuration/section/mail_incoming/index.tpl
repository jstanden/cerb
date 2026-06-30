<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{{'common.mail.incoming'|devblocks_translate|capitalize}}</div>
		<div class="cerb-ui-header--subtitle">Mailboxes, filtering, routing, logs, and more</div>
	</div>
</div>

<ul id="tabsSetupMailIncoming">
	<li data-alias="settings"><a href="#tabsSetupMailIncomingSettings">{'common.settings'|devblocks_translate|capitalize}</a></li>
	<li data-alias="mailboxes"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailboxes">{'common.mailboxes'|devblocks_translate|capitalize}</a></li>
	<li data-alias="filtering"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailFiltering">{'common.mail.filtering'|devblocks_translate|capitalize}</a></li>
	<li data-alias="routing"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailRouting">{'common.mail.routing'|devblocks_translate|capitalize}</a></li>
	<li data-alias="html"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailHtml">HTML</a></li>
	<li data-alias="import"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailImport">{'common.import'|devblocks_translate|capitalize}</a></li>
	<li data-alias="failed"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailFailed">Failed Messages</a></li>
	<li data-alias="relay"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailRelay">External Relay</a></li>
	<li data-alias="log"><a href="c=config&a=invoke&module=mail_incoming&action=renderTabMailLog">{'common.log'|devblocks_translate|capitalize}</a></li>
</ul>

<div id="tabsSetupMailIncomingSettings">
	<form id="frmSetupMailIncoming" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="mail_incoming">
	<input type="hidden" name="action" value="saveSettingsJson">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.settings'|devblocks_translate|capitalize}</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">By default, deliver new mail to</label>
				<div>
					<div class="cerb-ui-record-chooser" id="defaultGroupChooser">
						{if $default_group}
							<li data-context-id="{$default_group->id}" data-label="{$default_group->name}" data-image="{devblocks_url}c=avatars&context=group&context_id={$default_group->id}{/devblocks_url}?v={$default_group->updated}"></li>
						{/if}
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Reply to All</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input id="mailParserAutoReq" type="checkbox" name="parser_autoreq" value="1" {if $parser_autoreq}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="mailParserAutoReq">Send helpdesk replies to every recipient (To:/Cc:) on the original message.</label>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Always exclude these addresses as participants</label>
				<textarea name="parser_autoreq_exclude" rows="4">{$parser_autoreq_exclude}</textarea>
				<div class="cerb-ui-form--help">One address per line; use * for wildcards, like: *@do-not-reply.com</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Attachments</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input id="mailAttachmentsEnabled" type="checkbox" name="attachments_enabled" value="1" {if $attachments_enabled}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="mailAttachmentsEnabled">Allow incoming attachments</label>
				</div>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2 cerb-u-mt-2">
					<span>Maximum attachment size</span>
					<input type="text" name="attachments_max_size" value="{$attachments_max_size}" size="5" style="width:4em;"> MB
				</div>
				<div class="cerb-ui-form--help">Attachments larger than this will be ignored.</div>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Default Ticket Mask Format</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Mask <span class="cerb-ui-form--hint">(all uppercase; A-Z, 0-9, -)</span></label>
			<input type="text" name="ticket_mask_format" value="{$ticket_mask_format}" size="64">
			<div class="cerb-ui-form--help">
				{literal}
				<b>L</b> &mdash; letter, <b>N</b> &mdash; number, <b>C</b> &mdash; letter or number, <b>Y</b> &mdash; year, <b>M</b> &mdash; month, <b>D</b> &mdash; day, <b>{TEXT}</b> &mdash; literal text
				{/literal}
			</div>
			<div class="cerb-u-mt-2">
				<button type="button" class="cerb-ui-button tester"><span class="cerb-icons cerb-icon-gear"></span> {'common.test'|devblocks_translate|capitalize}</button>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Displaying HTML Messages</div>
		</div>

		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle"><input id="mailHtmlNoStripMicrosoft" type="checkbox" name="html_no_strip_microsoft" value="1" {if $html_no_strip_microsoft}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
			<label for="mailHtmlNoStripMicrosoft">Don't clean Microsoft Office formatting (when the Tidy extension is enabled)</label>
		</div>
	</div>

	<div>
		<button type="button" id="btnSaveMailIncoming" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
	</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmSetupMailIncoming');

	Devblocks.formDisableSubmit($frm);

	// Build the tab bar. A server-requested tab ({$tab}) wins over the remembered one.
	const ul = document.getElementById('tabsSetupMailIncoming');

	if(ul && window.CerbUI && CerbUI.Tabs) {
		let active;
		const requested = '{$tab}';

		if(requested) {
			const li = ul.querySelector('li[data-alias="' + requested + '"]');
			if(li) active = [...ul.querySelectorAll(':scope > li')].indexOf(li);
		}

		// Expose the instance so tab partials can refresh themselves after saving
		window.cerbMailIncomingTabs = new CerbUI.Tabs(ul, { remember: 'tabsSetupMailIncoming', active: active });
	}

	// Settings tab (inline) controls
	$frm.find('.cerb-peek-trigger').cerbPeekTrigger();
	if(window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser($frm.find('#defaultGroupChooser')[0], { context: '{$context_group}', name: 'default_group_id', emptyIcon: 'users', searchPlaceholder: "{'common.group'|devblocks_translate|capitalize|escape:'javascript' nofilter}" });

	if(window.CerbUI && CerbUI.Toggle) {
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });
	}

	$frm.find('#btnSaveMailIncoming').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxForm($frm);
	});

	$frm.find('button.tester').on('click', function(e) {
		const $button = $(this);

		const formData = new FormData($frm[0]);
		formData.set('c', 'config');
		formData.set('a', 'invoke');
		formData.set('module', 'mail_incoming');
		formData.set('action', 'testMask');

		genericAjaxPost(formData, null, null, function(json) {
			Devblocks.handleAjaxFormResponse($frm, json);
			$button.show();
		});
	});
});
</script>
