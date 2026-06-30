<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">{{'common.mail.outgoing'|devblocks_translate|capitalize}}</div>
		<div class="cerb-ui-header--subtitle">Mail transports, sender addresses, queue, logs, and more</div>
	</div>
</div>

<ul id="tabsSetupMailOutgoing">
	<li data-alias="transports"><a href="c=config&a=invoke&module=mail_outgoing&action=renderTabMailTransports">{'common.email_transports'|devblocks_translate|capitalize}</a></li>
	<li data-alias="senders"><a href="c=config&a=invoke&module=mail_outgoing&action=renderTabMailSenderAddresses">{'common.sender_addresses'|devblocks_translate|capitalize}</a></li>
	<li data-alias="settings"><a href="#tabsSetupMailOutgoingSettings">{'common.settings'|devblocks_translate|capitalize}</a></li>
	<li data-alias="templates"><a href="#tabsSetupMailOutgoingTemplates">Automated Email Templates</a></li>
	<li data-alias="queue"><a href="c=config&a=invoke&module=mail_outgoing&action=renderTabMailQueue">{'common.queue'|devblocks_translate|capitalize}</a></li>
	<li data-alias="log"><a href="c=config&a=invoke&module=mail_outgoing&action=renderTabMailDeliveryLog">{'common.log'|devblocks_translate|capitalize}</a></li>
</ul>

<div id="tabsSetupMailOutgoingSettings">
	<form id="frmSetupMailOutgoingSettings" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="mail_outgoing">
	<input type="hidden" name="action" value="saveSettingsJson">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.settings'|devblocks_translate|capitalize}</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">When not specified by a group, send mail from</label>
			<div>
				<div class="cerb-ui-record-chooser" id="mailDefaultFromChooser">
					{if $default_sender}
						<li data-context-id="{$default_sender->id}" data-label="{$default_sender->getNameWithEmail()}" data-image="{devblocks_url}c=avatars&context=address&context_id={$default_sender->id}{/devblocks_url}?v={$default_sender->updated_at}"></li>
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div>
		<button type="button" id="btnSaveMailOutgoingSettings" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
	</form>
</div>

<div id="tabsSetupMailOutgoingTemplates">
	<form id="frmSetupMailOutgoingTemplates" action="{devblocks_url}{/devblocks_url}" method="post" class="cerb-ui-form">
	<input type="hidden" name="c" value="config">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="mail_outgoing">
	<input type="hidden" name="action" value="saveTemplatesJson">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	{$template_placeholders = ['url' => 'Login URL']}
	{$default_template = $default_templates.worker_invite}

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Worker new account invitation</div>
		</div>

		{if is_array($templates) && array_key_exists('worker_invite', $templates)}
			{$template_body = $templates.worker_invite.body}
			{$template_send_as = $templates.worker_invite.send_as}
			{$template_subject = $templates.worker_invite.subject}
		{else}
			{$template_body = $default_template.body}
			{$template_send_as = $default_template.send_as}
			{$template_subject = $default_template.subject}
		{/if}

		{$send_from = $template_senders.worker_invite}

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.send.from'|devblocks_translate|capitalize}</label>
				<div>
					<div class="cerb-ui-record-chooser" id="workerInviteFromChooser">
						{if $send_from}
							<li data-context-id="{$send_from->id}" data-label="{$send_from->getNameWithEmail()}" data-image="{devblocks_url}c=avatars&context=address&context_id={$send_from->id}{/devblocks_url}?v={$send_from->updated_at}"></li>
						{/if}
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.send.as'|devblocks_translate|capitalize}</label>
				{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="templates[worker_invite][send_as]" value=$template_send_as context=$context_worker key_prefix="worker_" placeholders=$template_placeholders placeholder="(e.g. Company Support)" lines=2 gutter=false}
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.subject'|devblocks_translate|capitalize}</label>
				{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="templates[worker_invite][subject]" value=$template_subject context=$context_worker key_prefix="worker_" placeholders=$template_placeholders placeholder="(e.g. \"Your account recovery confirmation code\")" lines=2 gutter=false}
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.message'|devblocks_translate|capitalize}</label>
				{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="templates[worker_invite][body]" value=$template_body context=$context_worker key_prefix="worker_" placeholders=$template_placeholders lines=8}
			</div>
		</div>
	</div>

	{$template_placeholders = ['code' => 'Confirmation code', 'ip' => 'Client IP']}
	{$default_template = $default_templates.worker_recover}

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Worker account recovery instructions</div>
		</div>

		{if is_array($templates) && array_key_exists('worker_recover', $templates)}
			{$template_body = $templates.worker_recover.body}
			{$template_send_as = $templates.worker_recover.send_as}
			{$template_subject = $templates.worker_recover.subject}
		{else}
			{$template_body = $default_template.body}
			{$template_send_as = $default_template.send_as}
			{$template_subject = $default_template.subject}
		{/if}

		{$send_from = $template_senders.worker_recover}

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.send.from'|devblocks_translate|capitalize}</label>
				<div>
					<div class="cerb-ui-record-chooser" id="workerRecoverFromChooser">
						{if $send_from}
							<li data-context-id="{$send_from->id}" data-label="{$send_from->getNameWithEmail()}" data-image="{devblocks_url}c=avatars&context=address&context_id={$send_from->id}{/devblocks_url}?v={$send_from->updated_at}"></li>
						{/if}
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.send.as'|devblocks_translate|capitalize}</label>
				{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="templates[worker_recover][send_as]" value=$template_send_as context=$context_worker key_prefix="worker_" placeholders=$template_placeholders placeholder="(e.g. Company Support)" lines=2 gutter=false}
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'message.header.subject'|devblocks_translate|capitalize}</label>
				{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="templates[worker_recover][subject]" value=$template_subject context=$context_worker key_prefix="worker_" placeholders=$template_placeholders placeholder="(e.g. \"Your account recovery confirmation code\")" lines=2 gutter=false}
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.message'|devblocks_translate|capitalize}</label>
				{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="templates[worker_recover][body]" value=$template_body context=$context_worker key_prefix="worker_" placeholders=$template_placeholders lines=8}
			</div>
		</div>
	</div>

	<div>
		<button type="button" id="btnSaveMailOutgoingTemplates" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
	</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frmSettings = $('#frmSetupMailOutgoingSettings');
	const $frmTemplates = $('#frmSetupMailOutgoingTemplates');

	Devblocks.formDisableSubmit($frmSettings);
	Devblocks.formDisableSubmit($frmTemplates);

	// Build the tab bar. A server-requested tab ({$tab}) wins over the remembered one.
	const ul = document.getElementById('tabsSetupMailOutgoing');

	if(ul && window.CerbUI && CerbUI.Tabs) {
		let active;
		const requested = '{$tab}';

		if(requested) {
			const li = ul.querySelector('li[data-alias="' + requested + '"]');
			if(li) active = [...ul.querySelectorAll(':scope > li')].indexOf(li);
		}

		window.cerbMailOutgoingTabs = new CerbUI.Tabs(ul, { remember: 'tabsSetupMailOutgoing', active: active });
	}

	// Inline tab controls
	const $inline = $frmSettings.add($frmTemplates);
	$inline.find('.cerb-peek-trigger').cerbPeekTrigger();
	if(window.CerbUI && CerbUI.RecordChooser) {
		new CerbUI.RecordChooser($inline.find('#mailDefaultFromChooser')[0], { context: '{$context_address}', name: 'mail_default_from_id', emptyIcon: 'mail', query: 'mailTransport.id:>0 isBanned:n isDefunct:n' });
		new CerbUI.RecordChooser($inline.find('#workerInviteFromChooser')[0], { context: '{$context_address}', name: 'templates[worker_invite][send_from_id]', emptyIcon: 'mail', query: 'mailTransport.id:>0 isBanned:n isDefunct:n' });
		new CerbUI.RecordChooser($inline.find('#workerRecoverFromChooser')[0], { context: '{$context_address}', name: 'templates[worker_recover][send_from_id]', emptyIcon: 'mail', query: 'mailTransport.id:>0 isBanned:n isDefunct:n' });
	}

	$frmSettings.find('#btnSaveMailOutgoingSettings').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxForm($frmSettings);
	});

	$frmTemplates.find('#btnSaveMailOutgoingTemplates').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxForm($frmTemplates);
	});
});
</script>
