<div>
	<label><input type="checkbox" name="view_options[disable_watchers]" value="1" {if $view->options.disable_watchers}checked="checked"{/if}> Hide watchers column</label>
</div>

<fieldset class="peek peek-noborder black" style="margin-top:10px;">
	<legend>{'common.compose'|devblocks_translate|capitalize}</legend>
	
	When composing mail from this worklist, set the sender to:
	<div style="margin-left:10px;">
		<div class="cerb-ui-record-chooser cerb-bucket-chooser">
			{if array_key_exists('compose_bucket_id', $view->options)}
				{$bucket = DAO_Bucket::get($view->options.compose_bucket_id)}
				{if $bucket}
				<li data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$bucket->id}" data-label="{$bucket->name}"></li>
				{/if}
			{/if}
		</div>
	</div>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $view = $('#view{$view->id}');

	if(window.CerbUI && CerbUI.RecordChooser)
		$view.find('.cerb-bucket-chooser').each(function() {
			new CerbUI.RecordChooser(this, { context: '{CerberusContexts::CONTEXT_BUCKET}', name: 'view_options[compose_bucket_id]', emptyIcon: 'bucket' });
		});
});
</script>
