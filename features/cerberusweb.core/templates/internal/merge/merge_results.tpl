{$div_id = uniqid()}
<div id="{$div_id}">
	<div>
		Merged <b>{count($dicts)} {$aliases.plural|lower}</b> into
		<ul class="bubbles">
			<li>
				{if $context_ext->hasOption('avatars')}
				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$context_ext->id}&context_id={$dicts[$target_id]->id}{/devblocks_url}?v={$dicts[$target_id]->updated_at}">
				{/if}
				<a href="javascript:;" class="cerb-peek-trigger" data-context="{$dicts[$target_id]->_context}" data-context-id="{$dicts[$target_id]->id}">{$dicts[$target_id]->_label}</a>
			</li>
		</ul> 
	</div>
	
	<div style="margin:10px 0 0 0;">
		<button type="button" class="submit">{'common.ok'|devblocks_translate|upper}</button>
	</div>
</div>


<script type="text/javascript">
$(function() {
	var $div = $('#{$div_id}');
	var $popup = genericAjaxPopupFind($div);
	
	// Enable peeks
	$div.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	// Reload view
	{if $view_id}
	genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
	{/if}
	
	// Emit a record_merged event
	var evt = jQuery.Event('record_merged');
	evt.target_context_uri = '{$aliases.uri}';
	evt.target_context = '{$context_ext->id}';
	evt.target_id = {$target_id};
	$popup.trigger(evt);
	
	// Close button
	$div.find('button.submit').on('click', function(e) {
		genericAjaxPopupClose('peek');
	});
});
</script>