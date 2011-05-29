{$btnId = "btnWorkerChooser{microtime(true)|md5}"}

<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">is</option>
		<option value="in or null">is blank or</option>
		<option value="not in or null">is blank or not</option>
		<option value="not in">is not</option>
	</select>
</blockquote>

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

