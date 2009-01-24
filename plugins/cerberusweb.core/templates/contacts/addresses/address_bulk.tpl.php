<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/gear.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Bulk Update</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="address_ids" value="">
<div style="height:400px;overflow:auto;">

<h2>With:</h2>

<label><input type="radio" name="filter" value="" {if empty($address_ids)}checked{/if}> Whole list</label> 
<label><input type="radio" name="filter" value="checks" {if !empty($address_ids)}checked{/if}> Only checked</label> 
<br>
<br>

<div id="bulkUpdateCustom" style="display:block;">
<H2>Do:</H2>
<table cellspacing="0" cellpadding="2" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap">Organization:</td>
		<td width="100%">
			<div id="contactautocomplete" style="width:98%;" class="yui-ac">
				<input type="text" name="contact_org" id="contactinput" value="" class="yui-ac-input">
				<div id="contactcontainer" class="yui-ac-container"></div>
				<br>
				<br>
			</div>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap">Banned:</td>
		<td width="100%"><select name="is_banned">
			<option value=""></option>
			<option value="0">No</option>
			<option value="1">Yes</option>
      	</select></td>
	</tr>
	<!--
	<tr>
		<td width="0%" nowrap="nowrap">Country:</td>
		<td width="100%">
			<input type="text" name="country" value="" size="35">
		</td>
	</tr>
	-->
</table>

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
				{elseif $f->type=='T'}
					<textarea name="field_{$f_id}" rows="4" cols="50" style="width:98%;"></textarea><br>
				{elseif $f->type=='C'}
					<input type="checkbox" name="field_{$f_id}" value="1"><br>
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

<br>
</div>

<button type="button" onclick="ajax.saveAddressBatchPanel('{$view_id}');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>