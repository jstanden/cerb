{if !empty($fields)}
{* Check if any fields are used *}
{assign var=expanded value=false}
{foreach from=$fields key=f_id item=v}
	{assign var=k value='cf_'|cat:$f_id}
	{if !$expanded && (0==$v->group_id||$group_id==$v->group_id) && isset($filter->criteria.$k)}{assign var=expanded value=true}{/if}
{/foreach}
<label><input type="checkbox" onclick="toggleDiv('{$divName}',(this.checked?'block':'none'));if(!this.checked)checkAll('{$divName}',false);" {if $expanded}checked="checked"{/if}> <b>{$label}</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="{$divName}">
	{foreach from=$fields key=field_id item=field}
	{if (0==$field->group_id||$group_id==$field->group_id)}
	<tr>
		<td valign="top" width="1%" nowrap="nowrap">
			{assign var=cf value="cf_"|cat:$field_id}
			{assign var=crit_field value=$filter->criteria.$cf}
			<label><input type="checkbox" id="chkGetField{$field_id}" name="rules[]" value="cf_{$field_id}" onclick="toggleDiv('fieldGetValue{$field_id}',this.checked?'block':'none');" {if !is_null($crit_field)}checked="checked"{/if}> {$field->name}:</label>
		</td>
		<td width="99%">
			<div style="display:{if !is_null($crit_field)}block{else}none{/if};" id="fieldGetValue{$field_id}">
			{if 'S'==$field->type || 'T'==$field->type || 'U'==$field->type}
				<select name="value_cf_{$field_id}_oper">
					<option value="=" {if $crit_field.oper=="="}selected="selected"{/if}>{'search.oper.equals'|devblocks_translate}</option>
					<option value="!=" {if $crit_field.oper=="!="}selected="selected"{/if}>{'search.oper.equals.not'|devblocks_translate}</option>
				</select>
				<br>
				<input type="text" name="value_cf_{$field_id}" size="45" value="{$crit_field.value|escape}" onchange="document.getElementById('chkGetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
				<i>(use * for wildcards)</i>
			{elseif 'N'==$field->type}
				<select name="value_cf_{$field_id}_oper">
					<option value="=" {if $crit_field.oper=="="}selected="selected"{/if}>=</option>
					<option value="!=" {if $crit_field.oper=="!="}selected="selected"{/if}>!=</option>
					<option value=">" {if $crit_field.oper==">"}selected="selected"{/if}>&gt;</option>
					<option value="<" {if $crit_field.oper=="<"}selected="selected"{/if}>&lt;</option>
				</select>
				<input type="text" name="value_cf_{$field_id}" size="12" value="{$crit_field.value|escape}" onchange="document.getElementById('chkGetField{$field_id}').checked=((0==this.value.length)?false:true);">
			{elseif 'C'==$field->type}
				<label><input type="radio" name="value_cf_{$field_id}" value="1" {if !is_null($crit_field) && 1==$crit_field.value}checked="checked"{/if} onchange="document.getElementById('chkGetField{$field_id}').checked=((0==this.checked)?false:true);"> {$translate->_('common.yes')}</label>
				<label><input type="radio" name="value_cf_{$field_id}" value="0" {if !is_null($crit_field) && 0==$crit_field.value}checked="checked"{/if} onchange="document.getElementById('chkGetField{$field_id}').checked=((0==this.checked)?false:true);"> {$translate->_('common.no')}</label>
			{elseif 'E'==$field->type}
				<i>between:</i><br>
				<input type="text" name="value_cf_{$field_id}_from" size="20" value="{$crit_field.from|escape}" onchange="document.getElementById('chkGetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
				<i>and:</i><br>
				<input type="text" name="value_cf_{$field_id}_to" size="20" value="{$crit_field.to|escape}" onchange="document.getElementById('chkGetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
				<i>(+2 hours, now, next Friday 5pm, 2pm, Jan 25)</i>
			{elseif 'D'==$field->type || 'M'==$field->type || 'X'==$field->type}
				<i>is any of these:</i><br>
				{foreach from=$field->options item=option}
					<label><input type="checkbox" name="value_cf_{$field_id}[]" value="{$option|escape}" {if isset($crit_field.value.$option)}checked="checked"{/if}> {$option}</label><br>
				{/foreach}
			{elseif 'W'==$field->type}
				{if empty($workers)}
					{$workers = DAO_Worker::getAllActive()}
				{/if}
				<i>is any of these:</i><br>
				{foreach from=$workers item=worker key=worker_id}
					<label><input type="checkbox" name="value_cf_{$field_id}[]" value="{$worker->id|escape}" {if isset($crit_field.value.$worker_id)}checked="checked"{/if}> {$worker->getName()}</label><br>
				{/foreach}
			{/if}
			</div>
		</td>
	</tr>
	{/if}
	{/foreach}
</table>
{/if}
