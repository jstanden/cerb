{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-text" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<textarea name="prompts[{$var}]" style="width:100%;min-height:4.5em;box-sizing:border-box;" placeholder="{$placeholder}">{$value|default:$default}</textarea>
		{if $max_length && is_numeric($max_length)}
			<div data-cerb-character-count style="text-align:right;"></div>
		{/if}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $prompt = $('#{$element_id}');

	var $input = $prompt.find('textarea');
	var input = $input.get(0);

	// Move the cursor to the end of the text
	input.focus();
	input.setSelectionRange(input.value.length, input.value.length);
	
	var $counter = $prompt.find('[data-cerb-character-count]');
	var counter_max = {$max_length|json_encode};
	
	// [TODO] Links are always 23 characters on Twitter
	if($counter) {
		$input.on('input', function(e) {
			e.stopPropagation();
			
			var counter_cur = input.value.length;
			$counter.text(counter_cur + ' / ' + counter_max);

			if(counter_cur > counter_max) {
				$counter.css('color', 'red');
			} else {
				$counter.css('color', '');
			}
		});
	}
	
	$input.triggerHandler('keyup');
});
</script>