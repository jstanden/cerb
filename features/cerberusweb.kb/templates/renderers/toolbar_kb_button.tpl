{$uniq_id = uniqid()}
<button id="btn{$uniq_id}" type="button"><img src="{devblocks_url}c=resource&p=cerberusweb.kb&f=images/book_open2.gif{/devblocks_url}" align="top"> {$translate->_('common.knowledgebase')|capitalize}</button>

<script type="text/javascript">
$('#btn{$uniq_id}').click(function(e) {
	$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpen&context={CerberusContexts::CONTEXT_KB_ARTICLE}',null,false,'750');
	$chooser.one('chooser_save', function(event) {
		// ...
	});
});
</script>