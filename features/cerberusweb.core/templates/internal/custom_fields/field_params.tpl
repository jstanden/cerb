{if $model->type == Model_CustomField::TYPE_DROPDOWN || $model->type == Model_CustomField::TYPE_MULTI_CHECKBOX}
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">{'common.options'|devblocks_translate|capitalize}</label>
		<textarea rows="6" name="params[options]">{foreach from=$model->params.options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
		<div class="cerb-ui-form--help">(one option per line)</div>
	</div>
{elseif $model->type == Model_CustomField::TYPE_CURRENCY}
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Currency</label>
		{$currencies = DAO_Currency::getAll()}
		<select name="params[currency_id]">
		{foreach from=$currencies item=currency}
		<option value="{$currency->id}" {if $model->params.currency_id==$currency->id}selected="selected"{/if}>{$currency->name}</option>
		{/foreach}
		</select>
	</div>
{elseif $model->type == Model_CustomField::TYPE_DECIMAL}
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">{'dao.currency.decimal_at'|devblocks_translate|capitalize}</label>
		<input type="text" name="params[decimal_at]" maxlength="2" value="{$model->params.decimal_at|round}" placeholder="e.g. 2" style="width:6em;">
		<div class="cerb-ui-form--help">(e.g. <tt>4</tt> for <tt>1.2345</tt>)</div>
	</div>
{elseif $model->type == Model_CustomField::TYPE_LINK}
	{$contexts = Extension_DevblocksContext::getAll(false)}
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">To record type</label>
		{if $model->params.context}
			<input type="hidden" name="params[context]" value="{$model->params.context}">
			{$context = Extension_DevblocksContext::getByAlias($model->params.context, false)}
			{if $context->name}<div class="cerb-u-text-muted">{$context->name}</div>{/if}
		{else}
		<select name="params[context]" data-cerb-cfield-context>
			{foreach from=$contexts item=context}
			<option value="{$context->id}" data-cerb-ui-icon="{$context->params.icon|default:'collection'}" {if $model->params.context == $context->id}selected="selected"{/if}>{$context->name}</option>
			{/foreach}
		</select>
		{/if}
	</div>
{elseif $model->type == Model_CustomField::TYPE_MULTI_LINE}
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">{'common.format'|devblocks_translate|capitalize}</label>
		<select name="params[format]">
			<option value="" {if $model->params.format==''}selected="selected"{/if}>{'common.text'|devblocks_translate|capitalize}</option>
			<option value="markdown" {if $model->params.format=='markdown'}selected="selected"{/if}>{'common.format.markdown'|devblocks_translate|capitalize}</option>
		</select>
	</div>
{elseif $model->type == Model_CustomField::TYPE_WORKER}
	{$cf_worker_uid = uniqid('cf')}
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">{'common.options'|devblocks_translate|capitalize}</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle" id="{$cf_worker_uid}">
				<input type="checkbox" name="params[send_notifications]" value="1" {if $model->params.send_notifications}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="{$cf_worker_uid}">Send watcher notifications</label>
		</div>
	</div>
{/if}
