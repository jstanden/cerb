{$behavior = DAO_TriggerEvent::get($params.behavior_id)}

<b>{'common.behavior'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<button type="button" class="chooser-behavior" data-field-name="{$namePrefix}[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="" data-query-required="event:event.message.chat.portal disabled:n usableBy.bot:{$trigger->bot_id}"><span class="glyphicons glyphicons-search"></span></button>
	
	<ul class="bubbles chooser-container">
		{if $behavior}
			<li><input type="hidden" name="{$namePrefix}[behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
		{/if}
	</ul>
</div>

<div class="parameters">
{if $behavior}
{include file="devblocks:cerberusweb.core::events/_action_behavior_params.tpl" params=$params macro_params=$behavior->variables}
{/if}
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
	var $action = $('fieldset#{$namePrefix}');
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
					genericAjaxGet($behavior_params,'c=internal&a=showBehaviorParams&name_prefix={$namePrefix}&trigger_id=' + id);
				} else {
					$behavior_params.html('');
				}
			})
	;
});
</script>
