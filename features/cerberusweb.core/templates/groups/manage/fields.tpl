<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabFields">
<input type="hidden" name="group_id" value="{$group->id}">
<input type="hidden" name="allow_delete" value="0">

<fieldset>
	<legend>Ticket Fields ({$group->name})</legend>
	
	<table cellspacing="2" cellpadding="1" border="0">
		<tr style="background-color:rgb(230,230,230);">
			<td align="center"></td>
			<td><b>{$translate->_('common.order')|capitalize}</b></td>
			<td><b>Type</b></td>
			<td><b>Field</b></td>
		</tr>
	{counter name=field_pos start=0 print=false}
	{foreach from=$group_fields item=f key=field_id name=fields}
		{assign var=type_code value=$f->type}
		<tr>
			<td valign="top" align="center"><input type="checkbox" name="deletes[]" value="{$field_id}"></td>
			<td valign="top"><input type="text" name="orders[]" value="{counter name=field_pos}" size="3"> </td>
			<td valign="middle">{$types.$type_code}</td>
			<td valign="middle">
				<input type="hidden" name="ids[]" value="{$field_id}">
				<input type="text" name="names[]" value="{$f->name}" size="35" style="width:300;">
				{if $type_code != 'D' && $type_code != 'X'}
				<input type="hidden" name="options[]" value="">
				{/if}
			</td>
		</tr>
		{if $type_code=='D' || $type_code=='X'}
		<tr>
			<td></td>
			<td></td>
			<td></td>
			<td valign="top">
				<div class="subtle2">
				<b>Dropdown options:</b> (one per line)<br>
				<textarea cols="35" rows="5" name="options[]" style="width:300;">{foreach from=$f->options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
				</div>
			</td>
		</tr>
		{/if}
	{/foreach}
	</table>
	<br>
	
	<b>Add new custom field:</b><br>
	<select name="add_type" onchange="toggleDiv('addCustomFieldDropdown',(selectValue(this)=='D'||selectValue(this)=='X')?'block':'none');">
		{foreach from=$types item=type key=type_code}
		<option value="{$type_code}">{$type}</option>
		{/foreach}
	</select>
	 named 
	<input type="text" name="add_name" value="" size="45" maxlength="128">
	<br>
	
	<div id="addCustomFieldDropdown" style="display:none;">
	<b>Field Options:</b> (if dropdown, one per line)<br>
	<textarea name="add_options" rows="5" cols="50"></textarea><br>
	<br>
	</div>
	<br>
	
	<button type="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>
	<button type="button" onclick="this.form.allow_delete.value='1';this.form.submit();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'delete checked'|capitalize}</button>
</fieldset>

</form>
