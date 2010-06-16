<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showAddLanguagePanel',null,false,'500' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/dictionary.png{/devblocks_url}" align="top"> {$translate->_('translators.languages')|capitalize}</button>
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showFindStringsPanel',null,false,'550' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_refresh.png{/devblocks_url}" align="top"> {$translate->_('common.synchronize')|capitalize}</button>
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showImportStringsPanel',null,false,'500' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_up.png{/devblocks_url}" align="top"> {$translate->_('common.import')|capitalize}</button>
</form>

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}