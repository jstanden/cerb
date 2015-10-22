{$uniq_id = uniqid()}
<button id="btn{$uniq_id}" type="button"><span class="glyphicons glyphicons-book-open"></span> {'common.knowledgebase'|devblocks_translate|capitalize}</button>

<script type="text/javascript">
$('#btn{$uniq_id}').click(function(e) {
	$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpen&context={CerberusContexts::CONTEXT_KB_ARTICLE}',null,false,'750');
	$chooser.one('chooser_save', function(event) {
		// ...
	});
});
</script>