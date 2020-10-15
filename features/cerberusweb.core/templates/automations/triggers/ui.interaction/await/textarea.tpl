{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-text" id="{$element_id}">
	<h6>{$label}{if $is_required}<span>*</span>{/if}</h6>

	<div style="margin-left:10px;">
		<textarea name="prompts[{$var}]" style="width:100%;min-height:4.5em;box-sizing:border-box;" placeholder="{$placeholder}">{$value|default:$default}</textarea>
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
});
</script>