{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="mail">

{if empty($prefs.mail_reply_button)}{$v_reply_button = 0}{else}{$v_reply_button = $prefs.mail_reply_button}{/if}
{if empty($prefs.mail_reply_format)}{$v_reply_format = ''}{else}{$v_reply_format = $prefs.mail_reply_format}{/if}
{if empty($prefs.mail_signature_pos)}{$v_signature_pos = 0}{else}{$v_signature_pos = $prefs.mail_signature_pos}{/if}
{if empty($prefs.mail_status_compose)}{$v_status_compose = 'waiting'}{else}{$v_status_compose = $prefs.mail_status_compose}{/if}
{if empty($prefs.mail_status_reply)}{$v_status_reply = 'waiting'}{else}{$v_status_reply = $prefs.mail_status_reply}{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'preferences.account.mail.display'|devblocks_translate}</div></div>

	<div class="cerb-ui-form">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle"><input type="checkbox" name="mail_disable_html_display" id="mail_disable_html_display_{$form_id}" value="1" {if $prefs.mail_disable_html_display}checked{/if}><span class="cerb-ui-toggle--slider"></span></label>
			<label for="mail_disable_html_display_{$form_id}">{'preferences.account.mail.display.disable_html'|devblocks_translate}</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle"><input type="checkbox" name="mail_always_read_all" id="mail_always_read_all_{$form_id}" value="1" {if $prefs.mail_always_read_all}checked{/if}><span class="cerb-ui-toggle--slider"></span></label>
			<label for="mail_always_read_all_{$form_id}">{'preferences.account.mail.readall'|devblocks_translate}</label>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.reply'|devblocks_translate|capitalize}</div></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.mail.reply_button'|devblocks_translate}</label>
			<div>
				<input type="hidden" name="mail_reply_button" id="mail_reply_button_{$form_id}" value="{$v_reply_button}">
				<div class="cerb-ui-switcher" data-cerb-input="mail_reply_button_{$form_id}">
					<button type="button" data-value="0"{if $v_reply_button == 0} class="cerb-ui-switcher--active"{/if}>{'display.reply.quote'|devblocks_translate}</button>
					<button type="button" data-value="2"{if $v_reply_button == 2} class="cerb-ui-switcher--active"{/if}>{'display.reply.only_these_recipients'|devblocks_translate}</button>
					<button type="button" data-value="1"{if $v_reply_button == 1} class="cerb-ui-switcher--active"{/if}>{'display.reply.no_quote'|devblocks_translate}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.mail.reply_format'|devblocks_translate}</label>
			<div>
				<input type="hidden" name="mail_reply_format" id="mail_reply_format_{$form_id}" value="{$v_reply_format}">
				<div class="cerb-ui-switcher" data-cerb-input="mail_reply_format_{$form_id}">
					<button type="button" data-value=""{if $v_reply_format == ''} class="cerb-ui-switcher--active"{/if}>{'preferences.account.mail.reply_format.popup'|devblocks_translate}</button>
					<button type="button" data-value="inline"{if $v_reply_format == 'inline'} class="cerb-ui-switcher--active"{/if}>{'preferences.account.mail.reply_format.inline'|devblocks_translate}</button>
				</div>
			</div>
		</div>

		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle"><input type="checkbox" name="mail_reply_html" id="mail_reply_html_{$form_id}" value="1" {if $prefs.mail_reply_html}checked{/if}><span class="cerb-ui-toggle--slider"></span></label>
			<label for="mail_reply_html_{$form_id}">{'preferences.account.mail.reply.html'|devblocks_translate}</label>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'preferences.account.mail.signature'|devblocks_translate}</div></div>

	<div class="cerb-ui-form--field">
		<div>
			<input type="hidden" name="mail_signature_pos" id="mail_signature_pos_{$form_id}" value="{$v_signature_pos}">
			<div class="cerb-ui-switcher" data-cerb-input="mail_signature_pos_{$form_id}">
				<button type="button" data-value="0"{if $v_signature_pos == 0} class="cerb-ui-switcher--active"{/if}>{'preferences.account.mail.signature.none'|devblocks_translate}</button>
				<button type="button" data-value="3"{if $v_signature_pos == 3} class="cerb-ui-switcher--active"{/if}>{'preferences.account.mail.signature.above'|devblocks_translate}</button>
				<button type="button" data-value="1"{if $v_signature_pos == 1} class="cerb-ui-switcher--active"{/if}>{'preferences.account.mail.signature.above.cut'|devblocks_translate}</button>
				<button type="button" data-value="2"{if $v_signature_pos == 2} class="cerb-ui-switcher--active"{/if}>{'preferences.account.mail.signature.below'|devblocks_translate}</button>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.status'|devblocks_translate|capitalize}</div></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.mail.status.compose'|devblocks_translate}</label>
			<div>
				<input type="hidden" name="mail_status_compose" id="mail_status_compose_{$form_id}" value="{$v_status_compose}">
				<div class="cerb-ui-switcher" data-cerb-input="mail_status_compose_{$form_id}">
					<button type="button" data-value="open"{if $v_status_compose == 'open'} class="cerb-ui-switcher--active"{/if}>{'status.open'|devblocks_translate}</button>
					<button type="button" data-value="waiting"{if $v_status_compose == 'waiting'} class="cerb-ui-switcher--active"{/if}>{'status.waiting'|devblocks_translate}</button>
					<button type="button" data-value="closed"{if $v_status_compose == 'closed'} class="cerb-ui-switcher--active"{/if}>{'status.closed'|devblocks_translate}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.mail.status.reply'|devblocks_translate}</label>
			<div>
				<input type="hidden" name="mail_status_reply" id="mail_status_reply_{$form_id}" value="{$v_status_reply}">
				<div class="cerb-ui-switcher" data-cerb-input="mail_status_reply_{$form_id}">
					<button type="button" data-value="open"{if $v_status_reply == 'open'} class="cerb-ui-switcher--active"{/if}>{'status.open'|devblocks_translate}</button>
					<button type="button" data-value="waiting"{if $v_status_reply == 'waiting'} class="cerb-ui-switcher--active"{/if}>{'status.waiting'|devblocks_translate}</button>
					<button type="button" data-value="closed"{if $v_status_reply == 'closed'} class="cerb-ui-switcher--active"{/if}>{'status.closed'|devblocks_translate}</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div>
	<div class="cerb-ui-toolbar-strip">
		<button type="button" id="btnSave_{$form_id}" class="cerb-ui-toolbar-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI) {
		if(CerbUI.Toggle)
			$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

		if(CerbUI.Switcher) {
			$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) { input.value = value; input.dispatchEvent(new Event('change')); } }
				});
			});
		}
	}

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
