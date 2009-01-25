<table cellspacing="0" cellpadding="2" width="100%">
	<!-- Custom Fields -->
	<tr>
		<td colspan="2" align="center">&nbsp;</td>
	</tr>
	{foreach from=$custom_fields item=f key=f_id}
		<tr>
			<td width="1%" nowrap="nowrap">
				<label><input type="checkbox" name="field_ids[]" value="{$f_id}"><span style="font-size:90%;">{$f->name}:</span></label>
			</td>
			<td width="99%">
				{if $f->type=='S'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value=""><br>
				{elseif $f->type=='N'}
					<input type="text" name="field_{$f_id}" size="45" maxlength="255" value=""><br>
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;"></textarea><br>
				{elseif $f->type=='C'}
					<label><input type="checkbox" name="field_{$f_id}" value="1"> {$translate->_('common.yes')|capitalize}</label><br>
				{elseif $f->type=='D'}
					<select name="field_{$f_id}">
						<option value=""></option>
						{foreach from=$f->options item=opt}
						<option value="{$opt|escape}">{$opt}</option>
						{/foreach}
					</select><br>
				{elseif $f->type=='E'}
					<input type="text" name="field_{$f_id}" size="30" maxlength="255" value=""><button type="button" onclick="ajax.getDateChooser('dateCustom{$f_id}',this.form.field_{$f_id});">&nbsp;<img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/calendar.gif{/devblocks_url}" align="top">&nbsp;</button>
					<div id="dateCustom{$f_id}" style="display:none;position:absolute;z-index:1;"></div>
				{/if}	
			</td>
		</tr>
	{/foreach}
</table>
