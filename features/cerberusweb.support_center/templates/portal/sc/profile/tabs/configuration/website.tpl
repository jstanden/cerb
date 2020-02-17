{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="community_portal">
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
				<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span>
				<select name="visible_modules[]" onchange="toggleDiv('module{$module->manifest->id}','2'!=selectValue(this)?'block':'none');" style="margin-right:5px;min-width:150px;max-width:150px;">
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

<div class="status"></div>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $modules = $frm.find('FIELDSET[data-id="cerb-modules"]');
	var $status = $frm.find('div.status');
		
	$modules.find('DIV.container')
		.sortable({ items: 'DIV.drag', placeholder:'ui-state-highlight' })
	;
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.showError($status, json.error);
				} else if (json.message) {
					Devblocks.showSuccess($status, json.message);
				} else {
					Devblocks.showSuccess($status, "Saved!");
				}
			}
		});
	});
});
</script>