{$form_id = uniqid()}
<form id="{$form_id}" class="cerb-ui-form" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="profile">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'preferences.account.settings'|devblocks_translate|capitalize}</div></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.gender'|devblocks_translate|capitalize}</label>
			<div>
				<input type="hidden" name="gender" id="gender_{$form_id}" value="{$worker->gender}">
				<div class="cerb-ui-switcher" data-cerb-input="gender_{$form_id}">
					<button type="button" data-value="M"{if $worker->gender == 'M'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-gender-male"></span> {'common.gender.pronouns.male'|devblocks_translate}</button>
					<button type="button" data-value="F"{if $worker->gender == 'F'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-gender-female"></span> {'common.gender.pronouns.female'|devblocks_translate}</button>
					<button type="button" data-value=""{if empty($worker->gender)} class="cerb-ui-switcher--active"{/if}>{'common.gender.pronouns.neutral'|devblocks_translate}</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.location'|devblocks_translate|capitalize}</label>
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"></span>
				<input type="text" name="location" value="{$worker->location}" autocomplete="off" spellcheck="false" placeholder="e.g. Los Angeles, CA USA">
			</label>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.phone'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-phone-handset"></span>
					<input type="text" name="phone" value="{$worker->phone}" autocomplete="off" spellcheck="false">
				</label>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.mobile'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mobile"></span>
					<input type="text" name="mobile" value="{$worker->mobile}" autocomplete="off" spellcheck="false">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.dob'|devblocks_translate|capitalize}</label>
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-calendar"></span>
				<input type="text" name="dob" value="{if $worker->dob}{$worker->dob}{/if}" autocomplete="off" spellcheck="false" placeholder="YYYY-MM-DD">
			</label>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.photo'|devblocks_translate|capitalize}</label>
			<div>
				<span class="cerb-ui-avatar" style="width:100px;height:100px;font-size:42px;"
					data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}" data-name="avatar_image"
					data-avatar="{$worker->getName()}" data-avatar-seed="worker:{$worker->id}"
					data-avatar-image="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}"></span>
				<input type="hidden" name="avatar_image" value="">
			</div>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight"><div class="cerb-ui-header--title-sm">{'common.ui'|devblocks_translate|capitalize}</div></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.dark_mode'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle"><input type="checkbox" name="dark_mode" id="dark_mode_{$form_id}" value="1" {if $prefs.dark_mode == 1}checked{/if}><span class="cerb-ui-toggle--slider"></span></label>
				<label for="dark_mode_{$form_id}">{'common.enabled'|devblocks_translate|capitalize}</label>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'preferences.account.keyboard.shortcuts'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle"><input type="checkbox" name="keyboard_shortcuts" id="keyboard_shortcuts_{$form_id}" value="1" {if $prefs.keyboard_shortcuts eq 1}checked{/if}><span class="cerb-ui-toggle--slider"></span></label>
				<label for="keyboard_shortcuts_{$form_id}">{'common.enabled'|devblocks_translate|capitalize}</label>
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
		if(CerbUI.ImageEditor)
			$frm.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

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

		if(CerbUI.DatePicker)
			$frm.find('input[name=dob]').each(function() { new CerbUI.DatePicker(this, { outputFormat: 'YYYY-MM-DD' }); });
	}

	$frm.find('#btnSave_{$form_id}').on('click', function(e) {
		e.stopPropagation();
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>
