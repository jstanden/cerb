{*<h2>Translations</h2>*}

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showAddLanguagePanel',null,false,'500px' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/dictionary.png{/devblocks_url}" align="top"> {$translate->_('translators.languages')|capitalize}</button>
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showFindStringsPanel',null,false,'550px' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_refresh.png{/devblocks_url}" align="top"> {$translate->_('common.synchronize')|capitalize}</button>
	<button type="button" onclick="genericAjaxPanel('c=translators&a=showImportStringsPanel',null,false,'500px' );"><img src="{devblocks_url}c=resource&p=cerberusweb.translators&f=images/16x16/document_up.png{/devblocks_url}" align="top"> {$translate->_('common.import')|capitalize}</button>
</form>

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tplpath/internal/views/criteria_list.tpl" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>

