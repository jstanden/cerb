<div id="headerSubMenu">
	<div style="padding:5px;">
		<a href="{devblocks_url}c=contacts{/devblocks_url}">{$translate->_('core.menu.address_book')|lower}</a>
		 &raquo; 
		<a href="{devblocks_url}c=contacts&a=orgs{/devblocks_url}">{$translate->_('addy_book.tab.organizations')|lower}</a>
	</div>
</div>

<table cellpadding="2" cellspacing="0" border="0" width="100%">
<tr>
	<td valign="top">
		<h1>{$contact->name|escape}</h1>
		
		{if !empty($contact->street) || !empty($contact->country)}
			{if !empty($contact->street)}{$contact->street}, {/if}
			{if !empty($contact->city)}{$contact->city}, {/if}
			{if !empty($contact->province)}{$contact->province}, {/if}
			{if !empty($contact->postal)}{$contact->postal} {/if}
			{if !empty($contact->country)}{$contact->country}{/if}
			<br>
		{/if}
		{if !empty($contact->phone)}
			{$translate->_('contact_org.phone')|capitalize}: {$contact->phone}
			<br>
		{/if}
		{if !empty($contact->website)}<a href="{$contact->website}" target="_blank">{$contact->website}</a><br>{/if}

		{if is_array($breadcrumbs) && count($breadcrumbs) > 1}
		{foreach from=$breadcrumbs item=parent_org key=parent_org_id name=parent_orgs}
		{if !$smarty.foreach.parent_orgs.last}
			<a href="{devblocks_url}c=contacts&a=orgs&m=display&id={$parent_org_id}{/devblocks_url}">{$parent_org->name|escape}</a> 
		{else}
			<b>{$parent_org->name|escape}</b> 
		{/if}
		{if !$smarty.foreach.parent_orgs.last}
			&raquo;
		{/if}
		{/foreach}
		<br>
		{/if}
		
		<br>
	</td>
	<td align="right" valign="top">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="contacts">
		<input type="hidden" name="a" value="doOrgQuickSearch">
		<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
			<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
			<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
		</select><input type="text" name="query" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
		</form>
	</td>
</tr>
</table>

<div id="contactTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabNotes&org={$contact->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.notes')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabProperties&org={$contact->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.properties')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeople&org={$contact->id}{/devblocks_url}">{'addy_book.org.tabs.people'|devblocks_translate:$people_total|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabTasks&org={$contact->id}{/devblocks_url}">{'addy_book.org.tabs.tasks'|devblocks_translate:$tasks_total|escape:'quotes'}</a></li>

		{$tabs = [notes,properties,history,people,tasks]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$contact->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$tab_selected}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#contactTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
