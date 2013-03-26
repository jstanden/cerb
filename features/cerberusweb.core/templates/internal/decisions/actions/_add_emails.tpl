<div style="margin-left:10px;">
	Enter comma-separated email addresses:
	<br>
	
	<textarea rows="3" cols="60" name="{$namePrefix}[recipients]" style="width:100%;" class="placeholders email">{$params.recipients}</textarea>
</div>

<script type="text/javascript">
ajax.emailAutoComplete('fieldset#{$namePrefix} textarea.email', { multiple: true });
</script>