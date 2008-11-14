{*
<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxGet('cfgAttachmentsOutput','c=config&a=doAttachmentsSync');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/replace2.gif{/devblocks_url}" align="top"> {$translate->_('common.synchronize')|capitalize}</button>
</form>

<div id="cfgAttachmentsOutput"></div>
*}

<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tplpath/internal/views/criteria_list.tpl.php" divName="attachmentCriteriaDialog"}
			<div id="attachmentCriteriaDialog" style="visibility:visible;"></div>
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>

