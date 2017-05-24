<b>{'common.options'|devblocks_translate|capitalize}:</b> (one per line)
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[options]" rows="5" cols="45" style="width:100%;height:150px;" class="placeholders">{$params.options}</textarea>
</div>

<b>Custom CSS style:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[style]" style="width:100%;" value="{$params.style}" class="placeholders" placeholder="font-size:48px;">
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
});
</script>
