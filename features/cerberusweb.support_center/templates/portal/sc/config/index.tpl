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
<br>
<br>

<fieldset id="setupPortalModules" class="peek">
	<legend>Modules</legend>
	
	<div>
		<div class="headings">
			<div style="margin-left:24px;float:left;width:150px;"><b>Visibility</b></div>
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

{* Module config forms *}
{foreach from=$modules item=module}
	{assign var=module_id value=$module->manifest->id}
	<div id="module{$module->manifest->id}" style="display:{if isset($visible_modules.$module_id)}block{else}none{/if};">
		<fieldset class="peek">
			<legend>{$module->manifest->name}</legend>
			{if method_exists($module,'configure')}
			{$module->configure($instance)}
			{/if}
		</fieldset>
	</div>
{/foreach}

<script type="text/javascript">
$(function() {
	var $modules = $('FIELDSET#setupPortalModules');
	var $form = $modules.closest('form');
	
	$modules.find('DIV.container')
		.sortable({ items: 'DIV.drag', placeholder:'ui-state-highlight' })
	;
	
	$form.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
	
	$form.find('.cerb-chooser-trigger')
		.cerbChooserTrigger()
	;
});
</script>