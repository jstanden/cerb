<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmDisplayFields">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="setCustomFields">
<input type="hidden" name="ticket_id" value="{$ticket_id}">

{* [TODO] Display by Group *}
<blockquote style="margin:10px;">

	<table cellpadding="2" cellspacing="1" border="0">
	{assign var=last_group_id value=-1}
	
	{foreach from=$ticket_fields item=f key=f_id}
	{assign var=field_group_id value=$f->group_id}
	{if $field_group_id == 0 || $field_group_id == $ticket->team_id}
		{assign var=show_submit value=1}
		{if $field_group_id != $last_group_id}
			<tr>
				<td colspan="2"><H2>{if $f->group_id==0}Global{else}{$groups.$field_group_id->name}{/if} Fields</H2></td>
			</tr>
		{/if}
			<input type="hidden" name="field_ids[]" value="{$f_id}">
			
			<tr>
				<td valign="top" width="1%" nowrap="nowrap"><b>{$f->name}:</b></td>
				<td valign="top" width="99%">
					{* [TODO]: Filter by groups+global *}
					{if $f->type=='S'}
						<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$ticket_field_values.$f_id|escape:"htmlall"}"><br>
					{elseif $f->type=='T'}
						<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$ticket_field_values.$f_id}</textarea><br>
					{elseif $f->type=='C'}
						<input type="checkbox" name="field_{$f_id}" value="1" {if $ticket_field_values.$f_id}checked{/if}><br>
					{elseif $f->type=='D'}
						<select name="field_{$f_id}">{* [TODO] Fix selected *}
							<option value=""></option>
							{foreach from=$f->options item=opt}
							<option value="{$opt|escape}" {if $opt==$ticket_field_values.$f_id}selected{/if}>{$opt}</option>
							{/foreach}
						</select><br>
					{elseif $f->type=='E'}
						<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($ticket_field_values.$f_id)}{$ticket_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
						<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
					{/if}	
				</td>
			</tr>
		{assign var=last_group_id value=$f->group_id}
	{/if}
	{/foreach}
	
	</table>
	<br>
	
	{if $show_submit}
		<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	{else}
		No fields are defined for this group.
	{/if}
	
</blockquote>

</form>