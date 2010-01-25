<form>
	<button type="button" onclick="genericAjaxPanel('c=community&a=showAddTemplatePeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',this,false,'600px');"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/text_code_add.png{/devblocks_url}" align="top"> Add Custom Template</button>	
	<button type="button" onclick="genericAjaxPanel('c=community&a=showImportTemplatesPeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',this,false,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/import1.png{/devblocks_url}" align="top"> Import Templates</button>	
</form>

{*
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="portalsCriteriaDialog"}
			{<div id="portalsCriteriaDialog" style="visibility:visible;"></div>}
		</td>
		<td valign="top" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>
*}
<div id="view{$view->id}">{$view->render()}</div>