{if $model->type == 'D' || $model->type == 'X'}
	<fieldset>
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		
		<textarea cols="35" rows="6" name="params[options]" style="width:100%;">{foreach from=$model->params.options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
		<div>
			(one option per line)
		</div>
	</fieldset>
{elseif $model->type == 'L'}
	{$contexts = Extension_DevblocksContext::getAll(false)}
	<fieldset>
		<legend>To record type:</legend>
		
		{if $model->params.context}
			<input type="hidden" name="params[context]" value="{$model->params.context}">
			{$context = $contexts.{$model->params.context}}
			{if $context->name}
				{$context->name}
			{/if}
		{else}
		<select name="params[context]">
			{foreach from=$contexts item=context}
			<option value="{$context->id}" {if $model->params.context == $context->id}selected="selected"{/if}>{$context->name}</option>
			{/foreach}
		</select>
		{/if}
	</fieldset>
{elseif $model->type == 'W'}
	<fieldset>
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		
		<label><input type="checkbox" name="params[send_notifications]" value="1" {if $model->params.send_notifications}checked="checked"{/if}> Send watcher notifications</label>
	</fieldset>
{/if}
