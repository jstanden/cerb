<h2>{'translators.common'|devblocks_translate|capitalize}</h2>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=translations&action=showAddLanguagePanel',null,false,'500' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/dictionary.png{/devblocks_url}" align="top"> {'translators.languages'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=translations&action=showFindStringsPanel',null,false,'550' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_refresh.png{/devblocks_url}" align="top"> {'common.synchronize'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxPopup('peek','c=config&a=handleSectionAction&section=translations&action=showImportStringsPanel',null,false,'500' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_up.png{/devblocks_url}" align="top"> {'common.import'|devblocks_translate|capitalize}</button>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}