<form action="#" style="margin:5px;">
<button type="button" id="btnComment"><span class="cerb-sprite sprite-document_plain_yellow"></span> Comment</button>
</form>

{* Display Notes *}
{foreach from=$comments item=comment}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl"}
{/foreach}

<script type="text/javascript">
	$('#btnComment').click(function(event) {
		$popup = genericAjaxPopup('peek', 'c=internal&a=commentShowPopup&context={$context}&context_id={$context_id}', null, false, '550');
		$popup.one('comment_save', function(event) {
			$tabs = $('#btnComment').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
	});
</script>