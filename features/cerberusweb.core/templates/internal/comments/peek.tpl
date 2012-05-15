<form action="{devblocks_url}{/devblocks_url}" method="post" id="internalCommentPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="commentSavePopup">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">

<b>Author:</b> {$active_worker->getName()}
<div>
	<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea>
</div>
<div>
	<button type="button" onclick="ajax.chooserSnippet('snippets',$('#internalCommentPopup textarea[name=comment]'), { '{$context}':'{$context_id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
</div>
<br>

<b>Attachments:</b><br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_file"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	</ul>
</div>

<b>{'common.notify_watchers_and'|devblocks_translate}</b>:<br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
	{if !empty($notify_workers)}<span>(<a href="javascript:;" onclick="$(this).closest('span').siblings('ul.bubbles').html('');$(this).closest('span').remove();">clear</a>)</span>{/if}
	<ul class="chooser-container bubbles" style="display:block;">
	{if !empty($notify_workers) && !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{foreach from=$notify_workers item=notify_worker_id}
		{$notify_worker = $workers.$notify_worker_id}
		{if !empty($notify_worker)}
		<li>{$notify_worker->getName()}<input type="hidden" name="notify_worker_ids[]" value="{$notify_worker_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if}
	{/foreach}
	</ul>
</div>

<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	var $frm = $('#internalCommentPopup');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','Comment');
		
		$frm.find('button.submit').click(function() {
			$popup = genericAjaxPopupFetch('peek');
			genericAjaxPost('internalCommentPopup','','', null, { async: false } );
			$popup.trigger('comment_save');
			genericAjaxPopupClose('peek');
		});
	
		$frm.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		$frm.find('textarea').elastic();
		$frm.find('textarea').focus();
	});
</script>
