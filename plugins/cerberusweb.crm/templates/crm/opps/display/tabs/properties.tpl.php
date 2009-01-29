<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmOppFields">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="saveOppProperties">
<input type="hidden" name="opp_id" value="{$opp->id}">

<blockquote style="margin:10px;">

	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{$translate->_('crm.opportunity.name')|capitalize}: </td>
			<td width="100%"><input type="text" name="name" value="{$opp->name|escape}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('crm.opportunity.amount')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="amount" size="10" maxlength="12" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}0{else}{math equation="floor(x)" x=$opp->amount}{/if}" autocomplete="off">
				 . 
				<input type="text" name="amount_cents" size="3" maxlength="2" style="border:1px solid rgb(180,180,180);padding:2px;" value="{if empty($opp->amount)}00{else}{math equation="(x-floor(x))*100" x=$opp->amount}{/if}" autocomplete="off">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap">{$translate->_('crm.opportunity.worker_id')|capitalize}:</td>
			<td width="100%"><select name="worker_id">
				<option value="0"></option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=me_worker_id equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $opp->worker_id==$worker_id}selected{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
	      	{if !empty($me_worker_id)}
	      		<button type="button" onclick="this.form.worker_id.selectedIndex = {$me_worker_id};">me</button>
	      	{/if}
      		<button type="button" onclick="this.form.worker_id.selectedIndex = 0;">nobody</button>
			</td>
		</tr>
	</table>

	<table cellpadding="2" cellspacing="1" border="0">
	<tr>
		<td colspan="2">&nbsp;</td>
	</tr>
	{foreach from=$opp_fields item=f key=f_id}
		<tr>
			<td valign="top" width="1%" nowrap="nowrap">
				<input type="hidden" name="field_ids[]" value="{$f_id}">
				{$f->name}:
			</td>
			<td valign="top" width="99%">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$opp_field_values.$f_id|escape}"><br>
				{elseif $f->type=='N'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$opp_field_values.$f_id|escape}"><br>
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$opp_field_values.$f_id}</textarea><br>
				{elseif $f->type=='C'}
					<input type="checkbox" name="field_{$f_id}" value="1" {if $opp_field_values.$f_id}checked{/if}><br>
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">{* [TODO] Fix selected *}
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}" {if $opt==$opp_field_values.$f_id}selected{/if}>{$opt}</option>
						{/foreach}
					</select><br>
				{elseif $f->type=='E'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($opp_field_values.$f_id)}{$opp_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
					<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
				{/if}	
			</td>
		</tr>
	{/foreach}
	
	</table>
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</blockquote>

</form>