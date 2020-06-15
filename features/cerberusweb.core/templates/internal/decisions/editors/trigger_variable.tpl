{$var_type_label = $variable_types.{$var.type}}
	
<fieldset class="block-cell" style="margin-bottom:5px;">
	<legend style="cursor:move;">{$var_type_label} <span data-cerb-onhover class="glyphicons glyphicons-circle-minus" style="display:none;cursor:pointer;" onclick="$(this).closest('fieldset').remove();"></span></legend>
	<input type="hidden" name="var[]" value="{$seq}">
	<input type="hidden" name="var_key[]" value="{$var.key}">
	<input type="hidden" name="var_type[]" value="{$var.type}">

	<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<tr>
			<td width="1%" valign="middle" nowrap="nowrap">
				<select name="var_is_private[]">
					<option value="1" {if $var.is_private}selected="selected"{/if}>private</option>
					<option value="0" {if empty($var.is_private)}selected="selected"{/if}>public</option>
				</select>
				&nbsp;
			</td>
			<td width="99%" valign="middle">
				<input type="text" name="var_label[]" value="{$var.label}" size="45" style="width:100%;" placeholder="Variable name">
			</td>
		</tr>
		
		<tr>
			<td colspan="2" valign="top">
				<div style="margin:2px 0px 0px 10px;">
				{if $var.type == Model_CustomField::TYPE_SINGLE_LINE}
				<div>
					<label><input type="radio" name="var_params{$seq}[widget]" value="single" {if $var.params.widget=='single'}checked="checked"{/if}> Single line</label>
					<label><input type="radio" name="var_params{$seq}[widget]" value="multiple" {if $var.params.widget=='multiple'}checked="checked"{/if}> Multiple lines</label>
				</div>
				{elseif $var.type == Model_CustomField::TYPE_DROPDOWN}
				<div>
					<textarea name="var_params{$seq}[options]" rows="5" cols="45" style="width:100%;" placeholder="Enter one option per line">
{$var.params.options}</textarea>
				</div>
				{elseif $var.type == Model_CustomField::TYPE_LINK}
				<div>
					Type: 
					<select name="var_params{$seq}[context]">
						{foreach from=$context_mfts item=context_mft}
						<option value="{$context_mft->id}" {if $var.params.context==$context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
						{/foreach}
					</select>
				</div>
				{elseif $var.type == Model_CustomField::TYPE_NUMBER}
				{elseif $var.type == Model_CustomField::TYPE_DATE}
				{elseif $var.type == Model_CustomField::TYPE_CHECKBOX}
				<div>
					Default input to 
					<label><input type="radio" name="var_params{$seq}[checkbox_default_on]" value="1" {if $var.params.checkbox_default_on}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="var_params{$seq}[checkbox_default_on]" value="0" {if empty($var.params.checkbox_default_on)}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
				</div>
				{elseif $var.type == Model_CustomField::TYPE_WORKER}
				{elseif substr($var.type,0,4)=='ctx_'}
				{/if}
				</div>
			</td>
		</tr>
	</table>
</fieldset>
