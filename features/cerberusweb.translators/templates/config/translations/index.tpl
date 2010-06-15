<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showAddLanguagePanel',null,false,'500' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/dictionary.png{/devblocks_url}" align="top"> {$translate->_('translators.languages')|capitalize}</button>
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showFindStringsPanel',null,false,'550' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_refresh.png{/devblocks_url}" align="top"> {$translate->_('common.synchronize')|capitalize}</button>
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showImportStringsPanel',null,false,'500' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_up.png{/devblocks_url}" align="top"> {$translate->_('common.import')|capitalize}</button>
</form>

<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="$core_tpl/internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">{$view->render()}</div>

<script>
	$('#viewCustomFilters{$view->id}').bind('devblocks.refresh', function(event) {
		if(event.target == event.currentTarget)
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
	} );
</script>
