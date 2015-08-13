<form action="{devblocks_url}{/devblocks_url}" method="post" id="internalCommentPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="commentSavePopup">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Author:</b> {$active_worker->getName()}
<div>
	<textarea name="comment" rows="2" cols="60" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</div>
<div>
	<button type="button" onclick="ajax.chooserSnippet('snippets',$('#internalCommentPopup textarea[name=comment]'), { '{$context}':'{$context_id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
</div>
<br clear="all">

<b>Attachments:</b><br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="chooser-container bubbles" style="display:block;"></ul>
</div>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
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
			.autosize()
			.focus()
			.atwho({
				at: '@',
				{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
				{literal}insertTpl: '@${at_mention}',{/literal}
				data: atwho_workers,
				searchKey: '_index',
				limit: 10
			})
			;
	});
});
</script>
