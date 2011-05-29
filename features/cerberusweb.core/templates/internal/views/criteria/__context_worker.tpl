{$btnId = "btnWorkerChooser{microtime(true)|md5}"}

<b>{$translate->_('search.operator')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		{if empty($opers)}
		<option value="in">is</option>
		<option value="in or null">is blank or</option>
		<option value="not in or null">is blank or not</option>
		<option value="not in">is not</option>
		{else}
			{foreach from=$opers item=oper key=k}
				<option value="{$k}">{$oper}</option>
			{/foreach}
		{/if}
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

