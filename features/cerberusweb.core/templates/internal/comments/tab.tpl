<form action="#" style="margin:5px;">
<button type="button" id="btnComment" data-context="{CerberusContexts::CONTEXT_COMMENT}" data-context-id="0" data-edit="context:{$context} context.id:{$context_id}"><span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}</button>
</form>

{* Display Notes *}
{foreach from=$comments item=comment}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl"}
{/foreach}

<script type="text/javascript">
$(function() {
	$('#btnComment')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			var $tabs = $('#btnComment').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','active'));
			}
		})
	;
});
</script>