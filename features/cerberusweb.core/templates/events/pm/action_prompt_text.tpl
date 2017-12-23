<b>{'common.placeholder'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<input type="text" name="{$namePrefix}[placeholder]" value="{$params.placeholder}" style="width:100%;" class="placeholders">
</div>

<b>{'common.default'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[default]" class="placeholders">{$params.default}</textarea>
</div>

<b>{'common.options'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[mode]" value="" {if $params.mode != 'multiple'}checked="checked"{/if}> Single line</label>
	<label><input type="radio" name="{$namePrefix}[mode]" value="multiple" {if $params.mode == 'multiple'}checked="checked"{/if}> Multiple lines</label>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
