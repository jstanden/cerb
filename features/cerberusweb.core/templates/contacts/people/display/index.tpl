<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=people{/devblocks_url}">{$translate->_('addy_book.tab.people')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

{$primary_email = $person->getPrimaryAddress()}

<fieldset style="float:left;min-width:400px;">
	<legend>
		{$primary_email->getName()} &lt;{$primary_email->email}&gt;
	</legend>
	
	{if !empty($person->created)}<b>Created:</b> {$person->created|devblocks_date} ({$person->created|devblocks_prettytime})<br>{/if} 
	<b>Last Login:</b> {if empty($person->last_login)}{'common.never'|devblocks_translate|lower}{else}{$person->last_login|devblocks_date} ({$person->last_login|devblocks_prettytime}){/if}<br> 
	
	{*
	<form>
		<!-- Toolbar -->
		<button type="button" id="btnDisplayOrgEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	</form>
	*}
</fieldset>

<div style="clear:both;" id="contactPersonTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.contact_person&id={$person->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.contact_person&id={$person->id}{/devblocks_url}">{$translate->_('common.links')}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeopleAddresses&id={$person->id}{/devblocks_url}">{'Email Addresses'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeopleHistory&id={$person->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')}</a></li>

		{$tabs = [notes,links,history]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$person->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate}</i></a></li>
		{/foreach}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#contactPersonTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
