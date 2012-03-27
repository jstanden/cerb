{if !empty($presets)}
<b>Find objects matching all of these presets:</b>

<div style="margin:0px 0px 5px 10px;">
{foreach from=$presets item=preset key=preset_id}
	<label><input type="checkbox" name="{$namePrefix}[collections][]" value="{$preset_id}" {if in_array($preset_id, $params.collections)}checked="checked"{/if}> {$preset->name}</label>
	<br>
{/foreach}
</div>
{/if}

{if !empty($sort_fields)}
<b>Sort them by:</b>

<div style="margin:0px 0px 5px 10px;">
	<select name="{$namePrefix}[sort_by]">
		<option value=""></option>
		{foreach from=$sort_fields item=sort_field key=sort_field_key}
		{if !empty($sort_field->db_label)}
		<option value="{$sort_field_key}" {if $params.sort_by==$sort_field_key}selected="selected"{/if}>{$sort_field->db_label|capitalize}</option>
		{/if}
		{/foreach}
	</select>
	
	 in 
	
	<select name="{$namePrefix}[sort_asc]">
		<option value="1" {if !isset($params.sort_asc) || !empty($params.sort_asc)}selected="selected"{/if}>ascending</option>
		<option value="0" {if isset($params.sort_asc) && empty($params.sort_asc)}selected="selected"{/if}>descending</option>
	</select>
	
	order
</div>
{/if}

<b>And:</b>
<div style="margin:0px 0px 5px 10px;">
	Limit to <input type="text" name="{$namePrefix}[limit]" size="3" maxlength="3" value="{if !empty($params.limit)}{$params.limit|number_format}{else}100{/if}"> results 
</div>

<b>Then:</b>
<div style="margin:0px 0px 5px 10px;">
	<select name="{$namePrefix}[mode]">
		<option value="add" {if !isset($params.mode) || $params.mode=='add'}selected="selected"{/if}>Add these objects to the variable</option>
		<option value="subtract" {if $params.mode=='subtract'}selected="selected"{/if}>Remove these objects from the variable</option>
		<option value="replace" {if $params.mode=='replace'}selected="selected"{/if}>Replace the variable with these objects</option>
	</select>
</div>
