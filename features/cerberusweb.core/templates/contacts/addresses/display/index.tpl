<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=addresses{/devblocks_url}">{$translate->_('addy_book.tab.addresses')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

{*
<div style="float:right;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
	<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
</div>
*}

<fieldset style="float:left;min-width:400px;">
	{$addy_name = $address->getName()} 
	<legend>
		{if !empty($addy_name)}
			{$addy_name} &lt;{$address->email}&gt;
		{else}
			{$address->email}
		{/if}
	</legend>
	
	<form>
		<!-- Toolbar -->
		<button type="button" id="btnDisplayAddyEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	</form>
</fieldset>

<div style="clear:both;" id="contactTabs">
	<ul>
		{$tabs = [activity,notes,links]}
		{$point = 'cerberusweb.address.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={CerberusContexts::CONTEXT_ADDRESS}&context_id={$address->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={CerberusContexts::CONTEXT_ADDRESS}&id={$address->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={CerberusContexts::CONTEXT_ADDRESS}&id={$address->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		
		{*
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$address->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>
		*}

		{*
		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showAddressTab&ext_id={$tab_manifest->id}&org_id={$address->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
		*}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#contactTabs").tabs( { selected:{$tab_selected_idx} } );
	
		$('#btnDisplayAddyEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=contacts&a=showAddressPeek&address_id={$address->id}',null,false,'550');
			$popup.one('address_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=contacts&a=addresses&m=display&id={$address->id}-{$address->email|devblocks_permalink}{/devblocks_url}';
			});
		})
	});
</script>
