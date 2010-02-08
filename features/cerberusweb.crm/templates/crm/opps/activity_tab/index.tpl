<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
			<input type="hidden" name="c" value="crm">
			<input type="hidden" name="a" value="">
			{if $active_worker->hasPriv('crm.opp.actions.create')}<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppPanel&id=0&view_id={$view->id}',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/add.png{/devblocks_url}" align="top"> {$translate->_('crm.opp.add')}</button>{/if}
			{if $active_worker->hasPriv('crm.opp.actions.import')}<button type="button" onclick="genericAjaxPanel('c=crm&a=showImportPanel',null,false,'500');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/import1.png{/devblocks_url}" align="top"> {$translate->_('common.import')|capitalize}</button>{/if}
		</form>
	</td>
	<td width="98%" valign="middle"></td>
	<td width="1%" nowrap="nowrap" valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="crm">
		<input type="hidden" name="a" value="doQuickSearch">
		<b>{$translate->_('common.quick_search')}</b> <select name="type">
			<option value="email"{if $quick_search_type eq 'email'}selected{/if}>{$translate->_('crm.opportunity.email_address')|capitalize}</option>
			<option value="org"{if $quick_search_type eq 'org'}selected{/if}>{$translate->_('crm.opportunity.org_name')|capitalize}</option>
			<option value="title"{if $quick_search_type eq 'title'}selected{/if}>{$translate->_('crm.opportunity.name')|capitalize}</option>
		</select><input type="text" name="query" size="16"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
	</td>
</tr>
</table>

<table cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td width="0%" nowrap="nowrap" valign="top">
		<div style="width:220px;">
			{include file="file:$core_tpl/internal/views/criteria_list.tpl" divName="oppSearchFilters"}
			<div id="oppSearchFilters" style="visibility:visible;"></div>
		</div>
	</td>
	
	<td nowrap="nowrap" width="0%"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
	
	<td width="100%" valign="top">
		<div id="view{$view->id}">{$view->render()}</div>
	</td>
	
</tr>
</table>
