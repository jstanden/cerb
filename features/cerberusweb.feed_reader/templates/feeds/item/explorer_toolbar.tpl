{$model = DAO_FeedItem::get({$item->params.id})}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmExploreFeedItem">
	<input type="hidden" name="c" value="feeds">
	{if !$model->is_closed}
		<button type="button" class="status close"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> <label>{'common.close'|devblocks_translate|capitalize}</label></button>
	{else}
		<button type="button" class="status reopen"><span class="cerb-sprite sprite-folder_out"></span> <label>{'common.reopen'|devblocks_translate|capitalize}</label></button>
	{/if}
	
	<button type="button" class="edit"><span class="cerb-sprite sprite-document_edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
	
	<button type="button" onclick="genericAjaxPopup('peek','c=tasks&a=showTaskPeek&id=0&context={'cerberusweb.contexts.feed.item'}&context_id={$model->id}',null,true,'500')"><span class="cerb-sprite sprite-gear"></span> {'tasks.add'|devblocks_translate|capitalize}</button>
	
	{* [TODO] HACK!! *}
	{if DevblocksPlatform::isPluginEnabled('cerberusweb.feedback')}
	<button type="button" onclick="genericAjaxPopup('peek','c=feedback&a=showEntry&quote='+encodeURIComponent(Devblocks.getSelectedText())+'&url={$model->url|escape:'url'}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.feedback&f=images/question_and_answer.png{/devblocks_url}" align="top"> {$translate->_('feedback.button.capture')|capitalize}</button>
	{/if}
</form>

<script type="text/javascript">
	$('#frmExploreFeedItem BUTTON.status').click(function() {
		$btn = $(this);
		if($btn.hasClass('close')) {
			$btn.find('label').html('{'common.reopen'|devblocks_translate|capitalize}');
			$btn.removeClass('close').addClass('reopen').find('span').removeClass('sprite-tick-circle-frame').addClass('sprite-folder_out');
			genericAjaxGet('','c=feeds&a=exploreItemStatus&id={$model->id}&is_closed=1');
		} else {
			$btn.find('label').html('{'common.close'|devblocks_translate|capitalize}');
			$btn.removeClass('reopen').addClass('close').find('span').removeClass('sprite-folder_out').addClass('sprite-tick-circle-frame');
			genericAjaxGet('','c=feeds&a=exploreItemStatus&id={$model->id}&is_closed=0');
		}
	});
	
	$('#frmExploreFeedItem BUTTON.edit').click(function() {
		$popup = genericAjaxPopup('peek','c=feeds&a=showFeedItemPopup&id={$model->id}',null,true,'550');
		$popup.one('feeditem_save', function(event) {
			event.stopPropagation();
			document.location.reload();
		});
	});
</script>
