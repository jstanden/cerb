{$btnId = "btnWorkerChooser{microtime(true)|md5}"}
<input type="hidden" name="oper" value="in">

<b>{$translate->_('common.workers')|capitalize}:</b><br>

<div style="margin:0px 0px 10px 10px;">
<button type="button" class="chooser_worker" id="{$btnId}"><span class="cerb-sprite sprite-view"></span></button>
</div>

<script type="text/javascript">
	$('#{$btnId}').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:true });
	})
	.first().siblings('input:text').focus();
</script>

