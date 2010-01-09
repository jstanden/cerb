<div class="block">
{assign var=type value=$visit->get('import.last.type')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="frmCrmOppImport">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="doImport">

<H2>{'common.import'|devblocks_translate|capitalize}</H2>
<br>

<table cellpadding="2" cellspacing="0" border="0">
<tr>
	<td><b>{'crm.opp.import.from_file'|devblocks_translate}:</b></td>
	<td style="padding-left:10px;"><b>{'crm.opp.import.to_field'|devblocks_translate}:</b></td>
	<td style="padding-left:10px;"><b>{'crm.opp.import.find_dupes'|devblocks_translate}:</b></td>
</tr>
{foreach from=$parts item=part key=pos}
<tr>
	<td>{$part}:<input type="hidden" name="pos[]" value="{$pos}"></td>
	<td style="padding-left:10px;">
	<select name="field[]">
		<option value=""></option>
		{foreach from=$fields item=label key=field}
		{if $field == 'id'}
		{elseif $field == 'sync_id'}
		{else}
			<option value="{$field}">{$label}</option>
		{/if}
		{/foreach}
		
		{if !empty($custom_fields)}
		<optgroup label="- Custom Fields -">
		{foreach from=$custom_fields item=field}
			<option value="cf_{$field->id}">{$field->name}</option>
		{/foreach}
		{/if}
	</select>
	</td>
	<td align="center">
		<input type="checkbox" name="sync_dupes[]" value="{$pos}">
	</td>
</tr>
{/foreach}
</table>
<br>

<b>{'common.options'|devblocks_translate|capitalize}:</b><br>
<label><input type="checkbox" name="include_first" value="1"> {'crm.opp.import.options.import_first_row'|devblocks_translate}</label><br>
<label><input type="checkbox" name="is_blank_unset" value="1"> {'crm.opp.import.options.blank_fields'|devblocks_translate}</label><br>
<label><input type="checkbox" name="opt_assign" value="1"> {'crm.opp.import.options.set_owner'|devblocks_translate}</label> <select name="opt_assign_worker_id">
	<option value="">- {'common.nobody'|devblocks_translate|lower} -</option>
	{foreach from=$workers item=worker key=worker_id}
		<option value="{$worker_id}">{$worker->getName()}</option>
	{/foreach}
</select><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.upload')|capitalize}</button>
<button type="button" onclick="document.location='{devblocks_url}c=activity&a=opps{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
<br>
</form>
</div>
<br>
