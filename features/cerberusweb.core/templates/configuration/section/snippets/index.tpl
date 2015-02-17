<h2>Snippets</h2>

<div id="snippetTabs">
	<ul>
		{$tabs = []}
		{$point = 'setup.snippets.tab'}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabSnippets&context=all&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}">All Snippets</a></li>
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('snippetTabs');
	
	var tabs = $("#snippetTabs").tabs(tabOptions);
});
</script>

