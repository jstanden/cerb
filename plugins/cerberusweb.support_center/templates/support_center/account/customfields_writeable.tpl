{if $field->type==Model_CustomField::TYPE_SINGLE_LINE}
	<input type="text" name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}}" autocomplete="off">
{elseif $field->type==Model_CustomField::TYPE_URL}
	<input type="text" name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}}" autocomplete="off" class="url">
{elseif $field->type==Model_CustomField::TYPE_NUMBER}
	<input type="text" name="{$field_prefix}_{$field->id}" size="12" maxlength="20" value="{$values.{$field->id}}" autocomplete="off" class="number">
{elseif $field->type==Model_CustomField::TYPE_MULTI_LINE}
	<textarea name="{$field_prefix}_{$field->id}" rows="5" cols="60">{$values.{$field->id}}</textarea>
{elseif $field->type==Model_CustomField::TYPE_DROPDOWN}
	<select name="{$field_prefix}_{$field->id}">
		<option value=""></option>
		{foreach from=$field->params.options item=opt}
		<option value="{$opt}" {if $opt==$values.{$field->id}}selected="selected"{/if}>{$opt}
		{/foreach}
	</select>
{elseif $field->type==Model_CustomField::TYPE_WORKER}
	{if empty($workers)}
		{$workers = DAO_Worker::getAllActive()}
	{/if}
	<select name="{$field_prefix}_{$field->id}">
		<option value=""></option>
		{foreach from=$workers item=worker key=worker_id}
		<option value="{$worker_id}" {if $values.{$field->id}==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
		{/foreach}
	</select>
{elseif $field->type==Model_CustomField::TYPE_DATE}
	<input type="text" name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}|devblocks_date}" size="32" autocomplete="off">
{elseif $field->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
	{foreach from=$field->params.options item=opt}
	<label><input type="checkbox" name="{$field_prefix}_{$field->id}[]" value="{$opt}" {if is_array($values.{$field->id}) && in_array($opt,$values.{$field->id})}checked="checked"{/if}> {$opt}</label><br>
	{/foreach}
{elseif $field->type==Model_CustomField::TYPE_CHECKBOX}
	<label><input name="{$field_prefix}_{$field->id}" type="checkbox" value="Yes" {if $values.{$field_id}}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
{/if}
