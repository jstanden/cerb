{if !empty($fields)}
{* Check if any fields are used *}
{assign var=expanded value=false}
{foreach from=$fields key=f_id item=v}
	{assign var=k value='cf_'|cat:$f_id}
	{if !$expanded && (0==$v->group_id||$group_id==$v->group_id) && isset($filter->actions.$k)}{assign var=expanded value=true}{/if}
{/foreach}
<label><input type="checkbox" onclick="toggleDiv('{$divName}',(this.checked?'block':'none'));if(!this.checked)checkAll('{$divName}',false);" {if $expanded}checked="checked"{/if}> <b>{$label}</b></label><br>
<table width="100%" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="{$divName}">
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
			{if Model_CustomField::TYPE_SINGLE_LINE==$field->type || Model_CustomField::TYPE_MULTI_LINE==$field->type || Model_CustomField::TYPE_NUMBER==$field->type || Model_CustomField::TYPE_URL==$field->type}
				<input type="text" name="do_cf_{$field_id}" size="45" value="{$action_field.value}" onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;">
			{elseif Model_CustomField::TYPE_CHECKBOX==$field->type}
				<label><input type="radio" name="do_cf_{$field_id}" value="1" {if !is_null($action_field) && 1==$action_field.value}checked="checked"{/if} onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.checked)?false:true);"> {'common.yes'|devblocks_translate}</label>
				<label><input type="radio" name="do_cf_{$field_id}" value="0" {if !is_null($action_field) && 0==$action_field.value}checked="checked"{/if} onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.checked)?false:true);"> {'common.no'|devblocks_translate}</label>
			{elseif Model_CustomField::TYPE_CURRENCY==$field->type}
				{$currency = DAO_Currency::get($field->params.currency_id)}
				{$currency->symbol}
				<input type="text" name="do_cf_{$field_id}" size="24" maxlength="64" value="{$action_field.value}" class="currency">
				{$currency->code}
			{elseif Model_CustomField::TYPE_DATE==$field->type}
				<input type="text" name="do_cf_{$field_id}" size="30" value="{$action_field.value}" onchange="document.getElementById('chkSetField{$field_id}').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
				<i>(+2 hours, now, next Friday, 2pm, tomorrow 5pm)</i>
			{elseif Model_CustomField::TYPE_DECIMAL==$field->type}
				{$decimal_at = $field->params.decimal_at}
				<input type="text" name="do_cf_{$field_id}" size="24" maxlength="64" value="{$action_field.value}" class="decimal">
			{elseif Model_CustomField::TYPE_DROPDOWN==$field->type}
				<select name="do_cf_{$field_id}">
					{foreach from=$field->params.options item=option}
					<option value="{$option}" {if 0==strcasecmp($option,$action_field.value)}selected="selected"{/if}}> {$option}</option>
					{/foreach}
				</select>
			{elseif Model_CustomField::TYPE_FILE==$field->type}
				(file-based custom fields are not supported)
			{elseif Model_CustomField::TYPE_FILES==$field->type}
				(files-based custom fields are not supported)
			{elseif Model_CustomField::TYPE_WORKER==$field->type}
				{if empty($workers)}
					{$workers = DAO_Worker::getAllActive()}
				{/if}
				<select name="do_cf_{$field_id}">
				<option value=""></option>
				{foreach from=$workers item=worker key=worker_id}
					<option value="{$worker_id}" {if 0==strcasecmp($worker_id,$action_field.value)}selected="selected"{/if}}> {$worker->getName()}</option>
				{/foreach}
				</select>
			{elseif Model_CustomField::TYPE_MULTI_CHECKBOX==$field->type}
				{foreach from=$field->params.options item=raw_option}
					{assign var=option value='+'|cat:$raw_option}
					<label><input type="checkbox" name="do_cf_{$field_id}[]" value="{$option}" {if isset($action_field.value.$option)}checked="checked"{/if}> {$raw_option}</label><br>
				{/foreach}
			{elseif Model_CustomField::TYPE_LINK==$field->type}
				(link-based custom fields are not supported)
			{elseif Model_CustomField::TYPE_LIST==$field->type}
				(list-based custom fields are not supported)
			{else}
				(not supported)
			{/if}
			</div>
		</td>
	</tr>
	{/if}
	{/foreach}
</table>
{/if}
