<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
	
	{if $is_sent}
	<div>
		<label><input type="radio" name="{$namePrefix}[mode]" value="sent" {if $params.mode == 'sent'}checked="checked"{/if}> Only sent message</label>
		<label><input type="radio" name="{$namePrefix}[mode]" value="saved" {if $params.mode == 'saved'}checked="checked"{/if}> Only saved copy</label>
		<label><input type="radio" name="{$namePrefix}[mode]" value="" {if !$params.mode}checked="checked"{/if}> Both</label>
	</div>
	{/if}
</div>
<br>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>