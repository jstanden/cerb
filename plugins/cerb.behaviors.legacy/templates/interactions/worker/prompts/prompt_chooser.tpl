{$element_id = uniqid('prompt')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-chooser" id="{$element_id}">
	<h6>{$label}</h6>

	<div class="cerb-ui-record-chooser">
		{if $records && is_array($records)}
			{foreach from=$records item=record}
				<li data-context="{$record->_context}" data-context-id="{$record->id}" data-label="{$record->_label}"></li>
			{/foreach}
		{/if}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $element = $('#{$element_id}');

	if(window.CerbUI && CerbUI.RecordChooser) {
		$element.find('.cerb-ui-record-chooser').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{$record_type}',
				{if $selection == "single"}name: 'prompts[{$var}][]', multiple: false{else}name: 'prompts[{$var}]', multiple: true{/if},
				query: '{$record_query|cat:' '|cat:$record_query_required|trim|escape:'javascript' nofilter}'
			});
		});
	}
});
</script>
