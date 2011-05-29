{$btnId = "btnWorkerChooser{microtime(true)|md5}"}
<input type="hidden" name="oper" value="in">

<b>{$translate->_('common.workers')|capitalize}:</b><br>

<blockquote style="margin:5px;">
	<button type="button" class="chooser_worker" id="{$btnId}"><span class="cerb-sprite sprite-view"></span></button>
</blockquote>

<script type="text/javascript">
	$('#{$btnId}').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	})
	.first().siblings('input:text').focus();
</script>

