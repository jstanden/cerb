{if !empty($situation_reason)}
	<h2>{$translate->_('portal.contact.cfg.modify_situation')}</h2>
{else}
	<h2>{$translate->_('portal.contact.cfg.add_situation')}</h2>
{/if}
<input type="hidden" name="edit_reason" value="{$situation_reason|md5}">

<b>{$translate->_('portal.contact.cfg.reason_contacting')}</b> {$translate->_('portal.contact.cfg.reason_contacting_hint')}<br>
<input type="text" name="reason" size="65" value="{$situation_reason}"><br>
<br>

<b>{$translate->_('portal.cfg.deliver_to')}</b> {'portal.cfg.deliver_to_hint'|devblocks_translate:$default_from}<br>
<input type="text" name="to" size="65" value="{$situation_params.to}"><br>
<br>

<b>{$translate->_('portal.cfg.followup_questions')}</b> {$translate->_('portal.contact.cfg.followup_questions_hint')}<br>
{foreach from=$situation_params.followups key=q item=long name=followups}
	<input type="text" name="followup[]" size="65" value="{$q}"> 
	<label><input type="checkbox" name="followup_long[]" value="{$smarty.foreach.followups.index}" {if $long}checked{/if}> {$translate->_('portal.contact.cfg.long_answer')}</label><br>
{/foreach}

{math assign=dispatch_start equation="x+1" x=$smarty.foreach.followups.index}
{section name="dispatch" start=$dispatch_start loop=100 max=5}
	<input type="text" name="followup[]" size="65" value=""> 
	<label><input type="checkbox" name="followup_long[]" value="{$smarty.section.dispatch.index}"> {$translate->_('portal.contact.cfg.long_answer')}</label><br>
{/section}
{$translate->_('portal.contact.cfg.save_more_followups')}<br>
<br>
