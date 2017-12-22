<b>Mode:</b>
<div style="margin:0px 0px 5px 10px;">
	<label><input type="radio" name="{$namePrefix}[mode]" value="delta" {if $params.mode!='replace'}checked="checked"{/if}> Add/Remove</label>
	<label><input type="radio" name="{$namePrefix}[mode]" value="replace" {if $params.mode=='replace'}checked="checked"{/if}> {'common.replace'|devblocks_translate|capitalize}</label>
</div>

<b>One value per line:</b>
<div style="margin:0px 0px 5px 10px;">
	<textarea rows="3" cols="60" name="{$namePrefix}[values]" style="width:100%;" class="placeholders" data-editor-mode="ace/mode/plaintext">{$params.values}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $condition = $('#{$namePrefix}_{$nonce}');
	$condition.find('textarea').cerbCodeEditor();
})
</script>