{$behavior = DAO_TriggerEvent::get($params.behavior_id)}

<b>The current behavior should:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[return]" value="1" {if $params.return}checked="checked"{/if}> {'common.wait'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[return]" value="0" {if !$params.return}checked="checked"{/if}> {'common.exit'|devblocks_translate|capitalize}</label>
</div>

<b>{'common.behavior'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<button type="button" class="chooser-behavior" data-field-name="{$namePrefix}[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="" data-query-required="event:event.message.chat.worker disabled:n usableBy.bot:{$trigger->bot_id}"><span class="glyphicons glyphicons-search"></span></button>
	
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
	var $action = $('#{$namePrefix}_{$nonce}');
	var $behavior_params = $action.find('div.parameters');
	
	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$action.find('.chooser-behavior')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				var $bubbles = $action.find('ul.chooser-container');
				var $bubble = $bubbles.find('> li:first input:hidden');
				var id = $bubble.first().val();
				
				if(id) {
					genericAjaxGet(null,'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix={$namePrefix}&trigger_id=' + id, function(html) {
						var $html = $(html);
						$behavior_params.html($html);
						$html.find('.placeholders').cerbCodeEditor();
					});
				} else {
					$behavior_params.html('');
				}
			})
	;
});
</script>
