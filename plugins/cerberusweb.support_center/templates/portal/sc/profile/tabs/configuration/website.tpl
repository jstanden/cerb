{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="website">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.settings'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.page_title'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.cfg.default_if_blank'|devblocks_translate}</span></label>
			<input type="text" name="page_title" value="{$page_title}">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.logo_url'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.cfg.default_if_blank'|devblocks_translate}</span></label>
			<label class="cerb-ui-form--control"><span class="cerb-ui-form--control-icon cerb-icons cerb-icon-link"></span><input type="text" name="logo_url" value="{$logo_url}"></label>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.favicon_url'|devblocks_translate} <span class="cerb-ui-form--hint">{'portal.cfg.default_if_blank'|devblocks_translate}</span></label>
			<label class="cerb-ui-form--control"><span class="cerb-ui-form--control-icon cerb-icons cerb-icon-link"></span><input type="text" name="favicon_url" value="{$favicon_url}"></label>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.language'|devblocks_translate}</label>
			<select name="default_locale">
				{foreach from=$locales item=loc key=code}
				<option value="{$code}" {if $code==$default_locale}selected="selected"{/if}>{$loc}</option>
				{/foreach}
			</select>
		</div>
	</div>
</div>

<div data-id="cerb-modules" class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Modules</div>
	</div>

	<div class="cerb-u-flex cerb-u-gap-2 cerb-u-mb-2 cerb-u-text-muted cerb-u-fs-n1">
		<div style="width:150px;margin-left:1.75em;"><b>{'common.visibility'|devblocks_translate|capitalize}</b></div>
		<div><b>Module</b></div>
	</div>

	<div class="container cerb-ui-form">
		{foreach from=$modules item=module}
		{$module_id = $module->manifest->id}
		{if in_array($module_id, ['sc.controller.ajax','sc.controller.avatar'])}
		<input type="hidden" name="idx_modules[]" value="{$module->manifest->id}">
		<input type="hidden" name="visible_modules[]" value="0">
		{else}
		<div class="drag cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<span class="cerb-icons cerb-icon-move cerb-u-cursor-move cerb-u-text-muted" title="Drag to rearrange"></span>
			<select name="visible_modules[]" data-module-id="{$module->manifest->id}" style="min-width:150px;max-width:150px;">
				{if 'sc.controller.history' != $module->manifest->id && 'sc.controller.account' != $module->manifest->id}
				<option value="0" {if isset($visible_modules.$module_id) && '0'==$visible_modules.$module_id}selected="selected"{/if}>Everyone</option>
				{/if}
				{if 'sc.controller.login' != $module->manifest->id}
				<option value="1" {if isset($visible_modules.$module_id) && '1'==$visible_modules.$module_id}selected="selected"{/if}>Logged in</option>
				{/if}
				<option value="2" {if !isset($visible_modules.$module_id) || '2'==$visible_modules.$module_id}selected="selected"{/if}>Disabled</option>
			</select>
			<input type="hidden" name="idx_modules[]" value="{$module->manifest->id}">
			{$module->manifest->name}
		</div>
		{/if}
		{/foreach}
	</div>
</div>

<div data-id="cerb-security" class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.security'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Allow external images from these URL prefixes: <span class="cerb-ui-form--hint">one per line</span></label>
			<textarea name="security_csp_img_src" rows="6">{$security_csp_img_src}</textarea>
			<div class="cerb-ui-form--help">e.g. <code>https://example.com/</code></div>
		</div>
	</div>
</div>

<div data-id="cerb-stylesheet" class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Stylesheet</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'portal.cfg.stylesheet'|devblocks_translate}</label>
			<textarea name="user_stylesheet" data-editor-lines="15" spellcheck="false">{$user_stylesheet}</textarea>
		</div>
	</div>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $modules = $frm.find('[data-id="cerb-modules"]');

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($modules.find('div.container').get(0), { items: 'div.drag' });

	// Language picker — SelectMenu (native <select> keeps the POST value; adds type-to-filter).
	let localeEl = $frm.find('select[name=default_locale]')[0];
	if(localeEl && window.CerbUI && CerbUI.SelectMenu)
		new CerbUI.SelectMenu(localeEl);

	// Custom stylesheet — a plain ScriptingEditor (no autocomplete; CSS braces are not Twig).
	let cssEl = $frm.find('textarea[name=user_stylesheet]')[0];
	if(cssEl && window.CerbUI && CerbUI.ScriptingEditor)
		new CerbUI.ScriptingEditor(cssEl);

	$frm.find('button.save').on('click', function(e) {
		e.stopPropagation();
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
});
</script>
