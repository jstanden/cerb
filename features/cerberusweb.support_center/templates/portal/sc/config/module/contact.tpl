<label><input type="checkbox" name="allow_subjects" value="1" {if $allow_subjects}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.allow_custom_subjects')}</label><br>
<br>

<b>{$translate->_('portal.sc.cfg.open_ticket.attachments')}</b><br>
<label><input type="radio" name="attachments_mode" value="0" {if !$attachments_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.attachments.anybody')}</label>
<label><input type="radio" name="attachments_mode" value="1" {if 1==$attachments_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.attachments.logged_in')}</label>
<label><input type="radio" name="attachments_mode" value="2" {if 2==$attachments_mode}checked="checked"{/if}> {$translate->_('portal.sc.cfg.open_ticket.attachments.nobody')}</label>
<br>
<br>

<b>{$translate->_('portal.cfg.captcha')}</b> {$translate->_('portal.cfg.captcha_hint')}<br>
<label><input type="radio" name="captcha_enabled" value="1" {if $captcha_enabled}checked="checked"{/if}> {$translate->_('portal.cfg.enabled')}</label>
<label><input type="radio" name="captcha_enabled" value="0" {if !$captcha_enabled}checked="checked"{/if}> {$translate->_('portal.cfg.disabled')}</label>
<br>
<br>

{counter name=situation_idx start=0 print=false}
{foreach from=$dispatch item=params key=reason}
<div class="subtle" style="margin-bottom:10px;">
	<input type="hidden" name="situations[]" value="{$reason|md5}">
	<input type="text" name="order_situations[]" size="2" maxlength="3" value="{counter name=situation_idx}">
	&nbsp;<h2 style="display:inline;">{$reason}</h2>&nbsp;
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason={$reason|md5}&portal={$instance->code}');">{$translate->_('common.edit')|lower} </a>
	<br>
	<b>{$translate->_('portal.cfg.send_to')}</b> {$params.to}<br>
	{if is_array($params.followups)}
	{foreach from=$params.followups key=question item=field_id}
	<b>{$translate->_('portal.cfg.ask')}</b> {$question} 
	{if $field_id}
		{assign var=field value=$ticket_fields.$field_id}
		{assign var=field_group_id value=$field->group_id}
		<i>
		 &raquo; 
		{if isset($groups.$field_group_id)}{$groups.$field_group_id->name}: {/if}
		{$field->name}
		{assign var=field_type value=$field->type}
		{if isset($field_types.$field_type)}({$field_types.$field_type}){/if}
		</i>
	{/if}
	<br>
	{/foreach}
	<label><input type="checkbox" name="delete_situations[]" value="{$reason|md5}"> {$translate->_('portal.cfg.delete_situation')}</label>
	{/if}
</div>
{/foreach}

<div style="margin-left:10px;margin-bottom:10px;">
	<a href="#add_situation" onclick="genericAjaxGet('add_situation','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getContactSituation&reason=&portal={$instance->code}');">{$translate->_('portal.cfg.add_new_situation')} </a>
</div>

<div class="subtle2" id="add_situation">
{include file="devblocks:cerberusweb.support_center::portal/sc/config/module/contact/add_situation.tpl"}
</div>
