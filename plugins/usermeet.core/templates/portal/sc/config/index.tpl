<b>{$translate->_('portal.cfg.page_title')}</b> {$translate->_('portal.cfg.default_if_blank')}<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<b>{$translate->_('portal.cfg.language')}</b><br>
<select name="default_locale">
	{foreach from=$locales item=loc key=code}
	<option value="{$code|escape}" {if $code==$default_locale}selected="selected"{/if}>{$loc}</option>
	{/foreach}
</select>
<br>
<br>

<b>{$translate->_('portal.cfg.style_css')}</b><br>
<textarea name="style_css" style="width:90%;height:150px;">{$style_css}</textarea><br>
<br>

<b>{$translate->_('portal.sc.cfg.header')|escape}</b><br>
<table cellpadding="0" cellspacing="0" border="0">
<tr>
	<td valign="top" width="0%" nowrap="nowrap">
		<textarea cols="65" rows="8" name="header_html">{$header_html|escape}</textarea><br>
	</td>
	<td valign="top" width="100%" style="padding:10px;">
		<i>{$translate->_('portal.sc.cfg.example')}</i><br>
		{$translate->_('portal.sc.cfg.header_html_example')|escape|nl2br}<br>
	</td>
</tr>
</table>
<br>

<b>{$translate->_('portal.sc.cfg.footer')}</b><br>
<table cellpadding="0" cellspacing="0" border="0">
<tr>
	<td valign="top" width="0%" nowrap="nowrap">
		<textarea cols="65" rows="8" name="footer_html">{$footer_html|escape}</textarea><br>
	</td>
	<td valign="top" width="100%" style="padding:10px;">
		<i>{$translate->_('portal.sc.cfg.example')}</i><br>
		{$translate->_('portal.sc.cfg.footer_html_example')|escape|nl2br}<br>
	</td>
</tr>
</table>
<br>

<b>Options:</b><br>
<label><input type="checkbox" name="allow_logins" value="1" {if $allow_logins}checked="checked"{/if}> {$translate->_('portal.sc.cfg.allow_customer_logins')}</label><br>
<br>

<h2>Modules</h2>
<table cellpadding="0" cellspacing="5" border="0">
	<tr>
		<td><b>{$translate->_('common.order')|capitalize}</b></td>
		<td><b>Visibility</b></td>
		<td><b>Module</b></td>
	</tr>
	{counter name=pos start=0 print=false}
	{foreach from=$modules item=module}
		{assign var=module_id value=$module->manifest->id}
		<tr>
			<td align="center"><input type="text" name="pos_modules[]" size="2" value="{counter name=pos}"></td>
			<td align="center">
				<select name="visible_modules[]" onchange="toggleDiv('module{$module->manifest->id}','2'!=selectValue(this)?'block':'none');">
					{if 'sc.controller.history' != $module->manifest->id && 'sc.controller.account' != $module->manifest->id}
					<option value="0" {if isset($visible_modules.$module_id) && '0'==$visible_modules.$module_id}selected="selected"{/if}>Everyone</option>
					{/if}
					{if 'sc.controller.register' != $module->manifest->id}
					<option value="1" {if isset($visible_modules.$module_id) && '1'==$visible_modules.$module_id}selected="selected"{/if}>Logged in</option>
					{/if}
					<option value="2" {if !isset($visible_modules.$module_id) || '2'==$visible_modules.$module_id}selected="selected"{/if}>Disabled</option>
				</select>
			</td>
			<td><input type="hidden" name="idx_modules[]" value="{$module->manifest->id}">{$module->manifest->name}</td>
		</tr>
	{/foreach}
</table>
<br>

{* Module config forms *}
{foreach from=$modules item=module}
	{assign var=module_id value=$module->manifest->id}
	<div id="module{$module->manifest->id}" style="display:{if isset($visible_modules.$module_id)}block{else}none{/if};margin-left:10px;">
		<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:5px;">
		<h2 style="margin-bottom:0px;color:rgb(0,128,255);">{$module->manifest->name}</h2>
		</div>
		{$module->configure()}
	</div>
{/foreach}