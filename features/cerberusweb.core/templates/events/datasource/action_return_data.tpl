<b>{'common.template'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[data]" rows="3" cols="45" style="width:100%;height:6em;" class="placeholders">{$params.data}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
