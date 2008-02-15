{include file="$path/crm/submenu.tpl.php"}

<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<h1>Opportunities</h1>
	</td>
	<td width="99%" valign="middle">
		<a href="{devblocks_url}c=crm&a=opps{/devblocks_url}">overview</a>
		 | 	
		search
	</td>
</tr>
</table>

{if !empty($campaigns)}
<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
	<input type="hidden" name="c" value="crm">
	<input type="hidden" name="a" value="">
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppPanel&id=0&view_id={$view->id}',this,false,'500px',ajax.cbEmailPeek);"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/money.gif{/devblocks_url}" align="top"> Add Opportunity</button>
	<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppImportPanel&id=0',null,true,'500px');"><img src="{devblocks_url}c=resource&p=cerberusweb.crm&f=images/document_into.gif{/devblocks_url}" align="top"> Import</button>
</form>
{/if}

<table cellpadding="0" cellspacing="0" width="100%">

<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$tpl_path/internal/views/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>

</table>