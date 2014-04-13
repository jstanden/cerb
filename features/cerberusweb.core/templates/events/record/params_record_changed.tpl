<b>Record Type:</b>

<div style="margin-left:10px;">

<select name="event_params[context]">
	{foreach from=$contexts item=context}
	<option value="{$context->id}" {if $trigger->event_params.context==$context->id}selected="selected"{/if}>{$context->name}</option>
	{/foreach}
</select>

</div>