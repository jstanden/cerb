{if !empty($situation_reason)}
	<h2>{$situation_reason}</h2>
{else}
	<h2>{$translate->_('portal.sc.cfg.add_contact_situation')}</h2>
{/if}
<input type="hidden" name="edit_reason" value="{$situation_reason|md5}">

<b>{$translate->_('portal.sc.cfg.reason_contacting')}</b> {$translate->_('portal.sc.cfg.reason_contacting_hint')}<br>
<input type="text" name="reason" size="65" value="{$situation_reason|escape}"><br>
<br>

<b>{$translate->_('portal.cfg.deliver_to')}</b> {'portal.cfg.deliver_to_hint'|devblocks_translate:$default_from}<br>
<input type="text" name="to" size="65" value="{$situation_params.to|escape}"><br>
<br>

<b>{$translate->_('portal.cfg.followup_questions')}</b> {$translate->_('portal.sc.cfg.followup_questions_hint')}<br>
{foreach from=$situation_params.followups key=q item=field_id name=followups}
	{include file="$config_path/portal/sc/config/module/contact/add_situation_followups.tpl" field_id=$field_id}
{/foreach}

{math assign=dispatch_start equation="x+1" x=$smarty.foreach.followups.index}
{section name="dispatch" start=$dispatch_start loop=100 max=5}
	{include file="$config_path/portal/sc/config/module/contact/add_situation_followups.tpl" q=null field_id=null}
{/section}

{$translate->_('portal.sc.cfg.save_to_add_followups')}<br>
<br>


