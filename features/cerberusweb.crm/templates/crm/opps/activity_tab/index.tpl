<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td width="1%" nowrap="nowrap" valign="top" style="padding-right:5px;">
		<form action="{devblocks_url}{/devblocks_url}" method="post" style="padding-bottom:5px;">
			<input type="hidden" name="c" value="crm">
			<input type="hidden" name="a" value="">
			{if $active_worker->hasPriv('crm.opp.actions.create')}<button type="button" onclick="genericAjaxPanel('c=crm&a=showOppPanel&id=0&view_id={$view->id}',null,false,'500');"><span class="cerb-sprite sprite-add"></span> {$translate->_('crm.opp.add')}</button>{/if}
			{if $active_worker->hasPriv('crm.opp.actions.import')}<button type="button" onclick="genericAjaxPanel('c=crm&a=showImportPanel',null,false,'500');"><span class="cerb-sprite sprite-import"></span> {$translate->_('common.import')|capitalize}</button>{/if}
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
