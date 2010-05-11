{if $field->type=='S'}
	<input name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}|escape}" autocomplete="off" style="width:98%;">
{elseif $field->type=='U'}
	<input name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}|escape}" autocomplete="off" style="width:98%;" class="url">
{elseif $field->type=='N'}
	<input name="{$field_prefix}_{$field->id}" size="12" maxlength="20" value="{$values.{$field->id}|escape}" autocomplete="off" class="number">
{elseif $field->type=='T'}
	<textarea name="{$field_prefix}_{$field->id}" rows="5" cols="60" style="width:98%;">{$values.{$field->id}|escape}</textarea>
{elseif $field->type=='D'}
	<select name="{$field_prefix}_{$field->id}">
		<option value=""></option>
		{foreach from=$field->options item=opt}
		<option value="{$opt|escape}" {if $opt==$values.{$field->id}}selected="selected"{/if}>{$opt|escape}
		{/foreach}
	</select>
{elseif $field->type=='M'}
	<select name="{$field_prefix}_{$field->id}[]" size="5" multiple="multiple">
		{foreach from=$field->options item=opt}
		<option value="{$opt|escape}" {if is_array($values.{$field->id}) && in_array($opt,$values.{$field->id})}selected="selected"{/if}>{$opt|escape}
		{/foreach}
	</select><br>
	<i><small>{$translate->_('common.tips.multi_select')}</small></i>
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
	<input name="{$field_prefix}_{$field->id}" value="{$values.{$field->id}|devblocks_date|escape}" size="32" autocomplete="off">
{elseif $field->type=='X'}
	{foreach from=$field->options item=opt}
	<label><input type="checkbox" name="{$field_prefix}_{$field->id}[]" value="{$opt|escape}" {if is_array($values.{$field->id}) && in_array($opt,$values.{$field->id})}checked="checked"{/if}> {$opt}</label><br>
	{/foreach}
{elseif $field->type=='C'}
	<label><input name="{$field_prefix}_{$field->id}" type="checkbox" value="Yes" {if $values.{$field_id}}checked="checked"{/if}> {$translate->_('common.yes')|capitalize}</label>
{/if}
