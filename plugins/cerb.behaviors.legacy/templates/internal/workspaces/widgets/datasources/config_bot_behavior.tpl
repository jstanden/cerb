{$div_id = uniqid()}
<div id="{$div_id}">
	{$behavior_id = $widget->params.behavior_id}
	{$behavior = null}
	<div style="margin-left:10px;margin-bottom:0.5em;">
		<div class="cerb-ui-record-chooser">
			{if $behavior_id}
				{$behavior = DAO_TriggerEvent::get($behavior_id)}
				{if $behavior}
					<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
				{/if}
			{/if}
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $div = $('#{$div_id}');

	if(window.CerbUI && CerbUI.RecordChooser)
		$div.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
				name: 'params[behavior_id]',
				emptyIcon: 'branch',
				query: 'event:event.dashboard.widget.get_metric disabled:n'
			});
		});
});
</script>
