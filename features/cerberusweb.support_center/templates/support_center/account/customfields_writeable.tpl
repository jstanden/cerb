{if $field->type=='S'}
	<input type="text" name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}}" autocomplete="off">
{elseif $field->type=='U'}
	<input type="text" name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}}" autocomplete="off" class="url">
{elseif $field->type=='N'}
	<input type="text" name="{$field_prefix}_{$field->id}" size="12" maxlength="20" value="{$values.{$field->id}}" autocomplete="off" class="number">
{elseif $field->type=='T'}
	<textarea name="{$field_prefix}_{$field->id}" rows="5" cols="60">{$values.{$field->id}}</textarea>
{elseif $field->type=='D'}
	<select name="{$field_prefix}_{$field->id}">
		<option value=""></option>
		{foreach from=$field->params.options item=opt}
		<option value="{$opt}" {if $opt==$values.{$field->id}}selected="selected"{/if}>{$opt}
		{/foreach}
	</select>
{elseif $field->type=='W'}
	{if empty($workers)}
		{$workers = DAO_Worker::getAllActive()}
	{/if}
	<select name="{$field_prefix}_{$field->id}">
		<option value=""></option>
		{foreach from=$workers item=worker key=worker_id}
		<option value="{$worker_id}" {if $values.{$field->id}==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
		{/foreach}
	</select>
{elseif $field->type=='E'}
	<input type="text" name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}|devblocks_date}" size="32" autocomplete="off">
{elseif $field->type=='X'}
	{foreach from=$field->params.options item=opt}
	<label><input type="checkbox" name="{$field_prefix}_{$field->id}[]" value="{$opt}" {if is_array($values.{$field->id}) && in_array($opt,$values.{$field->id})}checked="checked"{/if}> {$opt}</label><br>
	{/foreach}
{elseif $field->type=='C'}
	<label><input name="{$field_prefix}_{$field->id}" type="checkbox" value="Yes" {if $values.{$field_id}}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
{/if}
