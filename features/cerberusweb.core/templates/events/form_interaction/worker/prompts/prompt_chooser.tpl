{$element_id = uniqid('prompt')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-chooser" id="{$element_id}">
	<h6>{$label}</h6>

	<button type="button" class="chooser-abstract" data-field-name="prompts[{$var}][]" data-context="{$record_type}" {if $selection == "single"}data-single="true"{/if} {if $autocomplete == 1}data-autocomplete="{$query}"{/if} data-query="{$record_query}" data-query-required="{$record_query_required}" data-shortcuts="false"><span class="glyphicons glyphicons-search"></span></button>
	<ul class="bubbles chooser-container">
		{if $records && is_array($records)}
			{foreach from=$records item=record}
				<li>
					<input type="hidden" name="prompts[{$var}][]" value="{$record->id}">
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{$record->_context}" data-context-id="{$record->id}">
						{$record->_label}
					</a>
				</li>
			{/foreach}
		{/if}
	</ul>
</div>

<script type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');
	var $button = $element.find('.chooser-abstract');

	$element.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	$button
		.cerbChooserTrigger()
		;
});
</script>