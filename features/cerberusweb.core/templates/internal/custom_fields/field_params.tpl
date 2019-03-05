{if $model->type == Model_CustomField::TYPE_DROPDOWN || $model->type == Model_CustomField::TYPE_MULTI_CHECKBOX}
	<fieldset>
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		
		<textarea cols="35" rows="6" name="params[options]" style="width:100%;">{foreach from=$model->params.options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
		<div>
			(one option per line)
		</div>
	</fieldset>
{elseif $model->type == Model_CustomField::TYPE_CURRENCY}
	<fieldset>
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		
		<b>Currency:</b>
		{$currencies = DAO_Currency::getAll()}
		<select name="params[currency_id]">
		{foreach from=$currencies item=currency}
		<option value="{$currency->id}" {if $model->params.currency_id==$currency->id}selected="selected"{/if}>{$currency->name}</option>
		{/foreach}
		</select>
	</fieldset>
{elseif $model->type == Model_CustomField::TYPE_DECIMAL}
	<fieldset>
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		
		<b>{'dao.currency.decimal_at'|devblocks_translate|capitalize}:</b>
		<input type="text" name="params[decimal_at]" size="3" maxlength="2" value="{$model->params.decimal_at|round}" style="width:4em;" placeholder="e.g. 2">
		<i>(e.g. <tt>4</tt> for <tt>1.2345</tt>)</i>
	</fieldset>
{elseif $model->type == Model_CustomField::TYPE_LINK}
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
{elseif $model->type == Model_CustomField::TYPE_WORKER}
	<fieldset>
		<legend>{'common.options'|devblocks_translate|capitalize}:</legend>
		
		<label><input type="checkbox" name="params[send_notifications]" value="1" {if $model->params.send_notifications}checked="checked"{/if}> Send watcher notifications</label>
	</fieldset>
{/if}
