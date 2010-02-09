<form>
	<button type="button" onclick="genericAjaxPanel('c=community&a=showAddTemplatePeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'600');"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/text_code_add.png{/devblocks_url}" align="top"> Add Custom Template</button>	
	<button type="button" onclick="genericAjaxPanel('c=community&a=showImportTemplatesPeek&portal={$tool->code}&view_id={$view->id|escape:'url'}',null,false,'500');"><span class="cerb-sprite sprite-import"></span> Import Templates</button>	
</form>

{*
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td valign="top" width="0%" nowrap="nowrap">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="portalsCriteriaDialog"}
			{<div id="portalsCriteriaDialog" style="visibility:visible;"></div>}
		</td>
		<td valign="top" width="0%" nowrap="nowrap" style="padding-right:5px;"></td>
		<td valign="top" width="100%">
			<div id="view{$view->id}">{$view->render()}</div>
		</td>
	</tr>
</table>
*}
<div id="view{$view->id}">{$view->render()}</div>