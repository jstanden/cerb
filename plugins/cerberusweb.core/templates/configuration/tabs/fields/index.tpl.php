<div id="tourConfigFields"></div>

<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="addCustomField">

<H2>Add Field</H2>

<b>Field Name:</b><br>
<input type="text" name="name" value="" size="45" maxlength="128"><br>
<br>

<b>Field Type:</b><br>
<select name="type" onchange="toggleDiv('addCustomFieldDropdown',(selectValue(this)=='D')?'block':'none');">
	{foreach from=$types item=type key=type_code}
	<option value="{$type_code}">{$type}</option>
	{/foreach}
</select><br>
<br>

<!-- 
<b>Group:</b><br>
<select name="group_id">
	<option value="0">- Global -</option>
	{foreach from=$groups item=group key=group_id}
		<option value="{$group_id}">{$group->name}</option>
	{/foreach}
</select><br>
<br>
-->

<div id="addCustomFieldDropdown" style="display:none;">
<b>Field Options:</b> (if dropdown, one per line)<br>
<textarea name="options" rows="5" cols="50"></textarea><br>
<br>
</div>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</form>
</div>
<br>

<div class="block">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveCustomFields">
<input type="hidden" name="allow_delete" value="0">
<h2>Ticket Fields (Global)</h2>
<br>

<table cellspacing="2" cellpadding="1" border="0">
	<tr style="background-color:rgb(230,230,230);">
		<td align="center"></td>
		<td><b>Order</b></td>
		<td><b>Type</b></td>
		<td><b>Field</b></td>
	</tr>
{counter name=field_pos start=0 print=false}
{foreach from=$ticket_fields item=f key=field_id name=fields}
	{assign var=type_code value=$f->type}
	<tr>
		<td valign="top" align="center"><input type="checkbox" name="deletes[]" value="{$field_id}"></td>
		<td valign="top"><input type="text" name="orders[]" value="{counter name=field_pos}" size="3"> </td>
		<td valign="middle">{$types.$type_code}</td>
		<td valign="middle">
			<input type="hidden" name="ids[]" value="{$field_id}">
			<input type="text" name="names[]" value="{$f->name|escape}" size="35" style="width:300;">
			{if $type_code != 'D'}
				<input type="hidden" name="options[]" value="">
			{/if}
		</td>
	</tr>
	{if $type_code=='D'}
	<tr>
		<td></td>
		<td></td>
		<td></td>
		<td valign="top">
			<div class="subtle2">
			<b>Dropdown options:</b> (one per line)<br>
			<textarea cols="35" rows="3" name="options[]" style="width:300;">{foreach from=$f->options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
			</div>
		</td>
	</tr>
	{/if}
{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="this.form.allow_delete.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {'delete checked'|capitalize}</button>
</form>
</div>
<br>

<script type="text/javascript">
	var configAjax = new cConfigAjax();
</script>