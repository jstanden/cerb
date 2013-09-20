{if $var.type == 'S'}
{$var_type_label = 'Text'}
{elseif $var.type == 'D'}
{$var_type_label = 'Picklist'}
{elseif $var.type == 'N'}
{$var_type_label = 'Number'}
{elseif $var.type == 'E'}
{$var_type_label = 'Date'}
{elseif $var.type == 'C'}
{$var_type_label = 'True/False'}
{elseif $var.type == 'W'}
{$var_type_label = 'Worker'}
{elseif substr($var.type,0,4)=='ctx_'}
	{$list_context_ext = substr($var.type,4)}
	{$list_context = $list_contexts.$list_context_ext}
	{$var_type_label = "(List) {$list_context->name}"}
{/if}
	
<fieldset class="peek" style="margin-bottom:5px;">
	<legend style="cursor:move;"><a href="javascript:;" onclick="$(this).closest('fieldset').remove();"><span class="cerb-sprite2 sprite-minus-circle" style="vertical-align:middle;"></span></a> {$var_type_label}</legend>
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
				{if $var.type == 'S'}
				<div>
					<label><input type="radio" name="var_params{$seq}[widget]" value="single" {if $var.params.widget=='single'}checked="checked"{/if}> Single line</label>
					<label><input type="radio" name="var_params{$seq}[widget]" value="multiple" {if $var.params.widget=='multiple'}checked="checked"{/if}> Multiple lines</label>
				</div>
				{elseif $var.type == 'D'}
				<div>
					<textarea name="var_params{$seq}[options]" rows="5" cols="45" style="width:100%;" placeholder="Enter one option per line">
{$var.params.options}</textarea>
				</div>
				{elseif $var.type == 'N'}
				{elseif $var.type == 'E'}
				{elseif $var.type == 'C'}
				{elseif $var.type == 'W'}
				{elseif substr($var.type,0,4)=='ctx_'}
				{/if}
				</div>
			</td>
		</tr>
	</table>
</fieldset>
