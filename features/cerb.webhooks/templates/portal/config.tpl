<div id="portalConfig{$instance->code}">

<fieldset class="peek">
	<legend>Webhook</legend>
	
	<div class="cerb-form">
		<div>
			<label><b>Use this behavior to respond to webhook requests:</b></label>
			<button type="button" class="chooser-behavior" data-field-name="params[webhook_behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="disabled:n" data-query-required="event:&quot;event.webhook.received&quot;"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $params.webhook_behavior_id}
					{$behavior = DAO_TriggerEvent::get($params.webhook_behavior_id)}
					{if $behavior}
						<li><input type="hidden" name="params[webhook_behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
					{/if}
				{/if}
			</ul>
		</div>
	</div>
</fieldset>

</div>

<script type="text/javascript">
$(function() {
	var $portal = $('#portalConfig{$instance->code}');
	
	$portal.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
	
	$portal.find('.chooser-behavior')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
			})
	;
});
</script>