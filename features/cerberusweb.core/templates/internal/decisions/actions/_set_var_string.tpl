<div>
	<textarea rows="3" cols="60" name="{$namePrefix}[value]" style="width:100%;" class="placeholders">{$params.value}</textarea>
</div>

<script type="text/javascript">
$condition = $('fieldset#{$namePrefix}');
$condition.find('textarea').elastic();
</script>
