{if $label}<b>{$label}:</b>{/if}
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[{$key}]" rows="3" cols="45" style="width:100%;height:{$textarea_height|default:'6em'};" class="placeholders">{$params.$key}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
