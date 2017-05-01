<b>{'common.placeholder'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[placeholder]" value="{$params.placeholder}" style="width:100%;" class="placeholders">
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
});
</script>
