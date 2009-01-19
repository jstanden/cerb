<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmOrgFields">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgFields">
<input type="hidden" name="org_id" value="{$org_id}">

<blockquote style="margin:10px;">

	<table cellpadding="2" cellspacing="1" border="0">
	<tr>
		<td colspan="2"><H2>Organization Fields</H2></td>
	</tr>
	{foreach from=$org_fields item=f key=f_id}
		<tr>
			<td valign="top" width="1%" nowrap="nowrap">
				<input type="hidden" name="field_ids[]" value="{$f_id}">
				<b>{$f->name}:</b>
			</td>
			<td valign="top" width="99%">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{$org_field_values.$f_id|escape}"><br>
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;">{$org_field_values.$f_id}</textarea><br>
				{elseif $f->type=='C'}
					<input type="checkbox" name="field_{$f_id}" value="1" {if $org_field_values.$f_id}checked{/if}><br>
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">{* [TODO] Fix selected *}
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}" {if $opt==$org_field_values.$f_id}selected{/if}>{$opt}</option>
						{/foreach}
					</select><br>
				{elseif $f->type=='E'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value="{if !empty($org_field_values.$f_id)}{$org_field_values.$f_id|devblocks_date}{/if}"><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
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