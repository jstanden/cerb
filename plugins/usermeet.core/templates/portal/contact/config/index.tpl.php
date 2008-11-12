<b>{$translate->_('portal.cfg.logo_url')}</b> {$translate->_('portal.cfg.logo_url_hint')}<br>
<input type="text" size="65" name="logo_url" value="{$logo_url}"><br>
<br>

<b>{$translate->_('portal.cfg.page_title')}</b> {$translate->_('portal.cfg.default_if_blank')}<br>
<input type="text" size="65" name="page_title" value="{$page_title}"><br>
<br>

<b>{$translate->_('portal.cfg.captcha')}</b> {$translate->_('portal.cfg.captcha_hint')}<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked{/if}> {$translate->_('portal.cfg.enabled')}</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked{/if}> {$translate->_('portal.cfg.disabled')}</label>
<br>
<br>

{foreach from=$dispatch item=params key=reason}
<div class="subtle" style="margin-bottom:10px;">
	<h2 style="display:inline;">{$reason|escape}</h2>&nbsp;
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason={$reason|md5}&portal={$instance->code}');">{$translate->_('common.edit')|lower} </a>
	<br>
	<b>{$translate->_('portal.cfg.send_to')}</b> {$params.to}<br>
	{if is_array($params.followups)}
	{foreach from=$params.followups key=question item=long}
	<b>{$translate->_('portal.cfg.ask')}</b> {$question|escape} {if $long}({$translate->_('portal.contact.cfg.long_answer')}){/if}<br>
	{/foreach}
	{/if}
	<label><input type="checkbox" name="delete_situations[]" value="{$reason|md5}"> {$translate->_('portal.cfg.delete_situation')}</label>
</div>
{/foreach}

<div style="margin-left:10px;margin-bottom:10px;">
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason=&portal={$instance->code}');">{$translate->_('portal.cfg.add_new_situation')} </a>
</div>

<div class="subtle2" id="add_situation">
{include file="$config_path/portal/contact/config/add_situation.tpl.php"}
</div>
