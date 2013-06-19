<h2>Custom Fields</h2>

<div id="cfTabs">
	<ul>
		{$tabs = [fields,fieldsets]}
		{$point = 'setup.fields.tab'}
		
		<li>
			<a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=fields&action=showFieldsTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Global</a>
		</li>
		<li>
			<a href="{devblocks_url}ajax.php?c=config&a=handleSectionAction&section=fields&action=showFieldsetsTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">Fieldsets</a>
		</li>
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#cfTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>
