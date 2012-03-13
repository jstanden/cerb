<h2>Snippets</h2>

<div id="snippetTabs">
	<ul>
		{$tabs = []}
		{$point = 'setup.snippets.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&context=all&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">All Snippets</a></li>
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var tabs = $("#snippetTabs").tabs( { selected:{$selected_tab_idx} } );
	});
</script>

