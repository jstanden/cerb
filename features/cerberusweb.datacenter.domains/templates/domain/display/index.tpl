{include file="devblocks:cerberusweb.datacenter.domains::domain/display/submenu.tpl"}

<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="top" style="padding-right:5px;">
		<h1 style="margin-bottom:5px;">{$domain->name|escape}</h1> 
		<form action="{devblocks_url}{/devblocks_url}" onsubmit="return false;" style="margin-bottom:5px;">
		<div style="margin-bottom:5px;">
		{if !empty($domain->server_id)}
			{$servers = DAO_Server::getAll()}
			{if isset($servers.{$domain->server_id})}
			<b>{'cerberusweb.datacenter.common.server'|devblocks_translate}:</b> 
			<a href="javascript:;" onclick="genericAjaxPopup('peek','c=datacenter&a=showServerPeek&view_id=&id={$domain->server_id}', null, false, '500');">{$servers.{$domain->server_id}->name|escape}</a>
			{/if}
		{/if}
		</div>
		
		<!-- Toolbar -->
		<div>
			<button type="button" id="btnDatacenterDomainEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
			
			{*
			{$toolbar_extensions = DevblocksPlatform::getExtensions('cerberusweb.task.toolbaritem',true)}
			{foreach from=$toolbar_extensions item=toolbar_extension}
				{$toolbar_extension->render($task)}
			{/foreach}
			*}
		</div>
		
		</form>
	</td>
	<td align="right" valign="top">
		{*
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
			<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
		</select><input type="text" name="query" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
		*}
	</td>
</tr>
</table>

<div id="datacenterDomainTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.datacenter.domain&id={$domain->id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize|escape}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.datacenter.domain&id={$domain->id}{/devblocks_url}">{'common.links'|devblocks_translate|escape}</a></li>

		{$tabs = [comments, links]}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#datacenterDomainTabs").tabs( { selected:{$tab_selected_idx} } );
		
		$('#btnDatacenterDomainEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=datacenter.domains&a=showDomainPeek&id={$domain->id}',null,false,'550');
			$popup.one('datacenter_domain_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=datacenter.domains&a=domain&id={$domain->id}{/devblocks_url}';
			});
		})
	});
</script>
