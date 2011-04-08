{if !empty($fields)}
{* Check if any fields are used *}
{assign var=expanded value=false}
{foreach from=$fields key=f_id item=v}
	{assign var=k value='cf_'|cat:$f_id}
	{if !$expanded && (0==$v->group_id||$group_id==$v->group_id) && isset($filter->actions.$k)}{assign var=expanded value=true}{/if}
{/foreach}
<label><input type="checkbox" onclick="toggleDiv('{$divName}',(this.checked?'block':'none'));if(!this.checked)checkAll('{$divName}',false);" {if $expanded}checked="checked"{/if}> <b>{$label}</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="{$divName}">
	{foreach from=$fields key=field_id item=field}
	{if (0==$field->group_id||$group_id==$field->group_id)}
	<tr>
		<td valign="top" width="1%" nowrap="nowrap">
			{assign var=cf value="cf_"|cat:$field_id}
			{assign var=action_field value=$filter->actions.$cf}
			<label><input type="checkbox" id="chkSetField{$field_id}" name="do[]" value="cf_{$field_id}" onclick="toggleDiv('fieldSetValue{$field_id}',this.checked?'block':'none');" {if !is_null($action_field)}checked="checked"{/if}> {$field->name}:</label>
		</td>
		<td width="99%">
			<div style="display:{if !is_null($action_field)}block{else}none{/if};" id="fieldSetValue{$field_id}">
			{if 'S'==$field->type || 'T'==$field->type || 'N'==$field->type || 'U'==$field->type}
				<input type="text" name="do_cf_{$field_id}" size="45" value="{$action_field.value}" onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;">
			{elseif 'C'==$field->type}
				<label><input type="radio" name="do_cf_{$field_id}" value="1" {if !is_null($action_field) && 1==$action_field.value}checked="checked"{/if} onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.checked)?false:true);"> {$translate->_('common.yes')}</label>
				<label><input type="radio" name="do_cf_{$field_id}" value="0" {if !is_null($action_field) && 0==$action_field.value}checked="checked"{/if} onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.checked)?false:true);"> {$translate->_('common.no')}</label>
			{elseif 'E'==$field->type}
				<input type="text" name="do_cf_{$field_id}" size="30" value="{$action_field.value}" onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
				<i>(+2 hours, now, next Friday, 2pm, tomorrow 5pm)</i>
			{elseif 'D'==$field->type}
				<select name="do_cf_{$field_id}">
					{foreach from=$field->options item=option}
					<option value="{$option}" {if 0==strcasecmp($option,$action_field.value)}selected="selected"{/if}}> {$option}</option>
					{/foreach}
				</select>
			{elseif 'W'==$field->type}
				{if empty($workers)}
					{$workers = DAO_Worker::getAllActive()}
				{/if}
				<select name="do_cf_{$field_id}">
				<option value=""></option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}" {if 0==strcasecmp($worker_id,$action_field.value)}selected="selected"{/if}}> {$worker->getName()}</option>
				{/foreach}
				</select>
			{elseif 'X'==$field->type}
				{foreach from=$field->options item=raw_option}
					{assign var=option value='+'|cat:$raw_option}
					<label><input type="checkbox" name="do_cf_{$field_id}[]" value="{$option}" {if isset($action_field.value.$option)}checked="checked"{/if}> {$raw_option}</label><br>
				{/foreach}
			{/if}
			</div>
		</td>
	</tr>
	{/if}
	{/foreach}
</table>
{/if}
