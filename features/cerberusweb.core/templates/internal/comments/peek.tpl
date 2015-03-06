<form action="{devblocks_url}{/devblocks_url}" method="post" id="internalCommentPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="commentSavePopup">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">

<b>Author:</b> {$active_worker->getName()}
<div>
	<div class="cerb-form-hint">{'comment.notify.at_mention'|devblocks_translate}</div>
	<textarea name="comment" rows="5" cols="60" style="width:98%;"></textarea>
</div>
<div>
	<button type="button" onclick="ajax.chooserSnippet('snippets',$('#internalCommentPopup textarea[name=comment]'), { '{$context}':'{$context_id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
</div>
<br clear="all">

<b>Attachments:</b><br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_file"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;"></ul>
</div>

<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#internalCommentPopup');
	
	$popup.one('popup_open',function(event,ui) {
		var $frm = $('#internalCommentPopup');
		
		$(this).dialog('option','title','Comment');
		
		$frm.find('button.submit').click(function() {
			var $popup = genericAjaxPopupFind('#internalCommentPopup');
			genericAjaxPost('internalCommentPopup','','', null, { async: false } );
			$popup.trigger('comment_save');
			genericAjaxPopupClose('comment');
		});
	
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		var $textarea = $frm.find('textarea');

		// Form hints
		
		$textarea
			.focusin(function() {
				$(this).siblings('div.cerb-form-hint').fadeIn();
			})
			.focusout(function() {
				$(this).siblings('div.cerb-form-hint').fadeOut();
			})
			;
		
		// @ mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};
		
		$textarea
			.elastic()
			.focus()
			.atwho({
				at: '@',
				{literal}tpl: '<li data-value="@${at_mention}">${name} <small style="margin-left:10px;">${title}</small></li>',{/literal}
				data: atwho_workers,
				limit: 10
			})			
			;
	});
});
</script>
