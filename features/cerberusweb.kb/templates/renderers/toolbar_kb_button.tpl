{$uniq_id = uniqid()}
<button id="btn{$uniq_id}" type="button" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_KB_ARTICLE}" data-query=""><span class="glyphicons glyphicons-book-open"></span> {'common.knowledgebase'|devblocks_translate|capitalize}</button>

<script type="text/javascript">
$(function() {
	$('#btn{$uniq_id}').cerbSearchTrigger();
});
</script>