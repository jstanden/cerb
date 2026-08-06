{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Comment on</label>
	<select name="{$namePrefix}[on]">
		{foreach from=$values_to_contexts item=context_data key=val_key}
		{if $context_data.label}<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>{/if}
		{/foreach}
	</select>
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.content'|devblocks_translate|capitalize}</label>
	<textarea name="{$namePrefix}[content]" rows="3" class="placeholders">{$params.content}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.format'|devblocks_translate|capitalize}</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[format]" value="" {if 'markdown' != $params.format}checked="checked"{/if}> Plaintext</label>
		<label><input type="radio" name="{$namePrefix}[format]" value="markdown" {if 'markdown' == $params.format}checked="checked"{/if}> Markdown</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.notify_workers'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">deprecated</span></label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_worker_picker.tpl" param_name="notify_worker_id" values_to_contexts=$values_to_contexts}
</div>

{if !empty($values_to_contexts)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Link to</label>
	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/actions/_shared_var_picker.tpl" param_name="link_to" values_to_contexts=$values_to_contexts}
</div>
{/if}
