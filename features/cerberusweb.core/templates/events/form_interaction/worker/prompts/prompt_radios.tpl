{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-radios" id="{$element_id}">
	<h6>{$label}</h6>

	<div class="cerb-form-builder-prompt-options">
		{$value = $dict->get($var)}
	
		{foreach from=$options item=option}
		<div style="{if $orientation == 'horizontal'}display:inline-block;{/if}">
			<label><input type="radio" name="prompts[{$var}]" value="{$option}" {if $value|default:$default==$option}checked="checked"{/if}> {$option}</label>
		</div>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');
	var $form = $element.closest('form');
	var $options = $element.find('.cerb-form-builder-prompt-options');
	
	$options.find('input[type=radio]')
		.on('click', function(e) {
			e.stopPropagation();
			
			if(1 == $form.find('.cerb-form-builder-prompt').length) {
				$form.submit();
			}
		})
		;
});
</script>