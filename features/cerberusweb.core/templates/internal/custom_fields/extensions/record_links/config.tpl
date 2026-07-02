<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">To record type</label>
	{if $field->params.context}
		<input type="hidden" name="params[context]" value="{$field->params.context}">
		{$context = $contexts.{$field->params.context}}
		{if $context->name}<div class="cerb-u-text-muted">{$context->name}</div>{/if}
	{else}
	<select name="params[context]" data-cerb-cfield-context>
		{foreach from=$contexts item=context}
		<option value="{$context->id}" data-cerb-ui-icon="{$context->params.icon|default:'collection'}" {if $field->params.context == $context->id}selected="selected"{/if}>{$context->name}</option>
		{/foreach}
	</select>
	{/if}
</div>
