<ul class="submenu">
</ul>
<div style="clear:both;"></div>

{$tabs = [orgs,people,addresses,lists]}
<div id="addyBookTabs">
	<ul>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showOrgsTab{/devblocks_url}">{$translate->_('addy_book.tab.organizations')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showPeopleTab{/devblocks_url}">{$translate->_('addy_book.tab.people')|escape:'quotes'}</a></li>
		<li><a href="{devblocks_url}ajax.php?c=contacts&a=showAddysTab{/devblocks_url}">{$translate->_('addy_book.tab.addresses')|escape:'quotes'}</a></li>
		{*<li><a href="{devblocks_url}ajax.php?c=contacts&a=showListsTab{/devblocks_url}">{$translate->_('addy_book.tab.lists')|escape:'quotes'}</a></li>*}
		{if $active_worker->hasPriv('core.addybook.import')}
			{$tabs[] = import}
			<li><a href="{devblocks_url}ajax.php?c=contacts&a=showImportTab{/devblocks_url}">{$translate->_('addy_book.tab.import')|escape:'quotes'}</a></li>
		{/if}
	</ul>
</div> 
<br>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#addyBookTabs").tabs( { selected:{$tab_selected_idx} } );
	});
</script>
