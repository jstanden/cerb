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
	<input type="text" name="followup[]" size="65" value="{$q|escape}"> 
	<!-- <label><input type="checkbox" name="followup_long[]" value="{$smarty.foreach.followups.index}" {if $long}checked{/if}> Long Answer</label><br>-->
	<select name="followup_fields[]">
		<option value="">-- {$translate->_('portal.sc.cfg.append_to_message')} --</option>
		{foreach from=$ticket_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		<option value="{$f_id}" {if $f_id==$field_id}selected{/if}>{$groups.$field_group_id->name}: {$f->name|escape}</option>
		{/foreach}
	</select>
	<br>
{/foreach}

{math assign=dispatch_start equation="x+1" x=$smarty.foreach.followups.index}
{section name="dispatch" start=$dispatch_start loop=100 max=5}
	<input type="text" name="followup[]" size="65" value=""> 
	<!-- <label><input type="checkbox" name="followup_long[]" value="{$smarty.section.dispatch.index}"> Long Answer</label><br> -->
	<select name="followup_fields[]">
		<option value="">-- {$translate->_('portal.sc.cfg.append_to_message')} --</option>
		{foreach from=$ticket_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		<option value="{$f_id}">{if isset($groups.$field_group_id)}{$groups.$field_group_id->name}: {/if}{$f->name|escape}</option>
		{/foreach}
	</select>
	<br>
{/section}
{$translate->_('portal.sc.cfg.save_to_add_followups')}<br>
<br>


