{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-buttons" id="{$element_id}">
	<h6>{$label}</h6>
	
	<div class="cerb-form-builder-prompt-options">
		{$value = $dict->get($var)}
		<input type="hidden" name="prompts[{$var}]" value="{$value}">
	
		{foreach from=$options item=option}
		<div style="margin:2px 0;{if $orientation == 'horizontal'}display:inline-block;{/if}">
			<button type="button" value="{$option}" {if $value==$option}style="border:2px solid rgb(39,123,214);"{/if}>{$option}</button>
		</div>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $element = $('#{$element_id}');
	var $form = $element.closest('form');
	var $hidden = $element.find('input[type=hidden]');
	var $options = $element.find('.cerb-form-builder-prompt-options');
	
	$options.find('button')
		.on('click', function(e) {
			e.stopPropagation();
			
			var $button = $(this);
			$hidden.val($button.val());
			
			if(1 == $form.find('.cerb-form-builder-prompt').length) {
				$form.submit();
				
			} else {
				$options.find('button').css('border', '');
				$button
					.css('border', '2px solid rgb(39,123,214)')
					;
			}
		})
		;
});
</script>