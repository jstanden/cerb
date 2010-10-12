<ul class="submenu">
	<li><a href="{devblocks_url}c=contacts&a=orgs{/devblocks_url}">{$translate->_('addy_book.tab.organizations')|lower}</a></li>
</ul>
<div style="clear:both;"></div>

<div style="clear:both; float:right;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doOrgQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span> <select name="type">
	<option value="name">{$translate->_('contact_org.name')|capitalize}</option>
	<option value="phone">{$translate->_('contact_org.phone')|capitalize}</option>
</select><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>
</div>

<fieldset style="float:left;min-width:400px;">
	<legend>{$contact->name|escape}</legend>
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
	
	<form>
		<!-- Toolbar -->
		<button type="button" id="btnDisplayOrgEdit"><span class="cerb-sprite sprite-document_edit"></span> Edit</button>
	</form>
</fieldset>

<div style="clear:both;" id="contactTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context=cerberusweb.contexts.org&id={$contact->id}{/devblocks_url}">{$translate->_('common.comments')|capitalize|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context=cerberusweb.contexts.org&id={$contact->id}{/devblocks_url}">{$translate->_('common.links')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabHistory&org={$contact->id}{/devblocks_url}">{$translate->_('addy_book.org.tabs.mail_history')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTabPeople&org={$contact->id}{/devblocks_url}">{'addy_book.org.tabs.people'|devblocks_translate:$people_total|escape:'quotes'}</a></li>

		{$tabs = [notes,links,history,people]}

		{foreach from=$tab_manifests item=tab_manifest}
			{$tabs[] = $tab_manifest->params.uri}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showTab&ext_id={$tab_manifest->id}&org_id={$contact->id}{/devblocks_url}"><i>{$tab_manifest->params.title|devblocks_translate|escape:'quotes'}</i></a></li>
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
		var tabs = $("#contactTabs").tabs( { selected:{$tab_selected_idx} } );
	
		$('#btnDisplayOrgEdit').bind('click', function() {
			$popup = genericAjaxPopup('peek','c=contacts&a=showOrgPeek&id={$contact->id}',null,false,'550');
			$popup.one('org_save', function(event) {
				event.stopPropagation();
				document.location.href = '{devblocks_url}c=contacts&a=orgs&m=display&id={$contact->id}{/devblocks_url}';
			});
		})
	});
</script>
