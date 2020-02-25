{if is_array($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
<select name="{$namePrefix}[on]" class="on">
	{foreach from=$values_to_contexts item=context_data key=val_key name=context_data}
	{if $smarty.foreach.context_data.first && empty($params.on)}{$params.on = $val_key}{/if}
	{$event_point = $context_to_macros.{$context_data.context}}
	{if $event_point && !$context_data.is_polymorphic}
	<option value="{$val_key}" data-event="{$event_point}" {if $params.on==$val_key}{$selected_event = $event_point}selected="selected"{/if}>{$context_data.label}</option>
	{elseif $context_data.is_polymorphic}
	<option value="{$val_key}" data-event="event.macro.*" {if $params.on==$val_key}{$selected_event = $event_point}selected="selected"{/if}>{$context_data.label}</option>
	{/if}
	{/foreach}
</select>
</div>
{/if}

<b>Run this behavior:</b>
{$behavior = null}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<button type="button" class="chooser-behavior" data-field-name="{$namePrefix}[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="event:{$selected_event} disabled:n usableBy.bot:{$trigger->bot_id}"><span class="glyphicons glyphicons-search"></span></button>
	
	<ul class="bubbles chooser-container">
		{if $params.behavior_id}
			{$behavior = DAO_TriggerEvent::get($params.behavior_id)}
			{if $behavior}
				<li><input type="hidden" name="{$namePrefix}[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
			{/if}
		{/if}
	</ul>
</div>

<div class="parameters">
{if $behavior}
{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" params=$params macro_params=$behavior->variables}
{/if}
</div>

<b>Also run behavior in simulator mode:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
</div>

<b>Save behavior data to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="24" value="{if !empty($params.var)}{$params.var}{else}_behavior{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $behavior_params = $action.find('div.parameters');
	var $bubbles = $action.find('ul.chooser-container');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$action.find('.chooser-behavior')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				var $bubble = $bubbles.find('> li:first input:hidden');
				var id = $bubble.first().val();
				
				if(id) {
					genericAjaxGet($behavior_params,'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix={$namePrefix}&trigger_id=' + id);
				} else {
					$behavior_params.html('');
				}
			})
		;
	
	$action.find('select.on').change(function(e) {
		$behavior_params.html('');
		
		var $on = $(this).find('option:selected');
		var event_point = $on.attr('data-event');
		
		$action.find('.chooser-behavior')
			.attr('data-query', 'event:' + event_point + ' disabled:n usableBy.bot:{$trigger->bot_id}')
			;
		
		$bubbles.find('> *').remove();
	});
});
</script>