{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="website">

<fieldset id="setupPortalModules" class="peek">
	<legend>{'common.settings'|devblocks_translate|capitalize}</legend>

	<b>{'portal.cfg.page_title'|devblocks_translate}</b> {'portal.cfg.default_if_blank'|devblocks_translate}<br>
	<input type="text" size="65" name="page_title" value="{$page_title}"><br>
	<br>
	
	<b>{'portal.cfg.logo_url'|devblocks_translate}</b> {'portal.cfg.default_if_blank'|devblocks_translate}<br>
	<input type="text" name="logo_url" value="{$logo_url}" size="64"><br>
	<br>
	
	<b>{'portal.cfg.favicon_url'|devblocks_translate}</b> {'portal.cfg.default_if_blank'|devblocks_translate}<br>
	<input type="text" name="favicon_url" value="{$favicon_url}" size="64"><br>
	<br>
	
	<b>{'portal.cfg.language'|devblocks_translate}</b><br>
	<select name="default_locale">
		{foreach from=$locales item=loc key=code}
		<option value="{$code}" {if $code==$default_locale}selected="selected"{/if}>{$loc}</option>
		{/foreach}
	</select>
</fieldset>

<fieldset data-id="cerb-modules" class="peek">
	<legend>Modules</legend>
	
	<div>
		<div class="headings">
			<div style="margin-left:24px;float:left;width:150px;"><b>{'common.visibility'|devblocks_translate|capitalize}</b></div>
			<div style="margin-left:5px;float:left;"><b>Module</b></div>
		</div>
		
		<div class="container" style="clear:both;">
			{foreach from=$modules item=module}
			{$module_id = $module->manifest->id}
			{if in_array($module_id, ['sc.controller.ajax','sc.controller.avatar'])}
			<input type="hidden" name="idx_modules[]" value="{$module->manifest->id}">
			<input type="hidden" name="visible_modules[]" value="0">
			{else}
			<div class="drag" style="margin:5px;">
				<span class="cerb-icons cerb-icon-move" style="cursor:move;margin-right:0.5em;" title="Drag to rearrange"></span>
				<select name="visible_modules[]" data-module-id="{$module->manifest->id}" style="margin-right:5px;min-width:150px;max-width:150px;">
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
</fieldset>

<fieldset data-id="cerb-security" class="peek">
	<legend>{{'common.security'|devblocks_translate|capitalize}}</legend>

	<b>Allow external images from these URL prefixes:</b> (one per line)
	<div>
		<textarea name="security_csp_img_src" style="height:8.5em;width:90%;">{$security_csp_img_src}</textarea>
		<div>(e.g. <code>https://example.com/</code>)</div>
	</div>
</fieldset>

<fieldset data-id="cerb-stylesheet" class="peek">
	<legend>Stylesheet</legend>

	<b>{'portal.cfg.stylesheet'|devblocks_translate}</b>
	<div>
		<textarea name="user_stylesheet" class="cerb-editor" data-editor-mode="ace/mode/css" style="height:20em;width:90%;">{$user_stylesheet}</textarea>
	</div>
</fieldset>

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $modules = $frm.find('FIELDSET[data-id="cerb-modules"]');

	$modules.find('DIV.container')
		.sortable({ items: 'DIV.drag', placeholder:'ui-state-highlight' })
	;

	$frm.find('textarea.cerb-editor')
		.cerbCodeEditor()
	;

	$frm.find('button.submit').on('click', function(e) {
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