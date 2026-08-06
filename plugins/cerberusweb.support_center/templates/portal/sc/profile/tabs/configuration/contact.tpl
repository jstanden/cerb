{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="contact">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Open a ticket</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.sc.cfg.open_ticket.allow_headers'|devblocks_translate}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-4">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input type="checkbox" name="allow_cc" id="allowCc_{$form_id}" value="1" {if $allow_cc}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="allowCc_{$form_id}">{'message.header.cc'|devblocks_translate|capitalize}</label>
				</div>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input type="checkbox" name="allow_subjects" id="allowSubjects_{$form_id}" value="1" {if $allow_subjects}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="allowSubjects_{$form_id}">{'message.header.subject'|devblocks_translate|capitalize}</label>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.sc.cfg.open_ticket.attachments'|devblocks_translate}</label>
			<div>
				<input type="hidden" name="attachments_mode" id="attachMode_{$form_id}" value="{if 1==$attachments_mode}1{elseif 2==$attachments_mode}2{else}0{/if}">
				<div class="cerb-ui-switcher" data-cerb-input="attachMode_{$form_id}">
					<button type="button" data-value="0"{if !$attachments_mode} class="cerb-ui-switcher--active"{/if}>{'common.everyone'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="1"{if 1==$attachments_mode} class="cerb-ui-switcher--active"{/if}>{'portal.sc.cfg.open_ticket.attachments.logged_in'|devblocks_translate}</button>
					<button type="button" data-value="2"{if 2==$attachments_mode} class="cerb-ui-switcher--active"{/if}>{'common.nobody'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.captcha'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.cfg.captcha_hint'|devblocks_translate}</span></label>
			<div>
				<input type="hidden" name="captcha_enabled" id="captcha_{$form_id}" value="{if 1==$captcha_enabled}1{elseif 2==$captcha_enabled}2{else}0{/if}">
				<div class="cerb-ui-switcher" data-cerb-input="captcha_{$form_id}">
					<button type="button" data-value="1"{if 1==$captcha_enabled} class="cerb-ui-switcher--active"{/if}>{'common.everyone'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="2"{if 2==$captcha_enabled} class="cerb-ui-switcher--active"{/if}>{'common.anonymous'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="0"{if !$captcha_enabled} class="cerb-ui-switcher--active"{/if}>{'common.nobody'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">Situations</div>
		<div class="cerb-ui-header--right">
			<button id="btnAddSituation" type="button" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-plus"></span> {'portal.cfg.add_new_situation'|devblocks_translate|capitalize}</button>
		</div>
	</div>

	<div id="situations" class="container">
	{foreach from=$dispatch item=params key=reason}
		{include file="devblocks:cerberusweb.support_center::portal/sc/profile/tabs/configuration/contact/situation.tpl" reason=$reason params=$params}
	{/foreach}
	</div>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');

	$frm.find('button.save').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});

	// Toggles (allow CC/subject) + Switchers (attachments / captcha) bound to their hidden inputs.
	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	if(window.CerbUI && CerbUI.Switcher) {
		$frm.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
			let input = document.getElementById(this.getAttribute('data-cerb-input'));
			if(!input) return;
			new CerbUI.Switcher(this, { value: input.value, onSelect: function(value) { input.value = value; } });
		});
	}

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($frm.find('div#situations').get(0), { items: '.cerb-ui-panel.drag' });

	$frm.find('BUTTON#btnAddSituation')
	.click(function() {
		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'community_portal');
		formData.set('action', 'showConfigTab');
		formData.set('config_tab', 'contact');
		formData.set('tab_action', 'addContactSituation');
		formData.set('portal_id', '{$portal->id}');

		genericAjaxPost(formData, '', '', function(html) {
			let $clone = $(html);
			$('DIV#situations').append($clone);
		});
	})
	;
});
</script>
