<h2>Plugin Library</h2>

{*
<div style="float:right;">
	{include file="devblocks:wgm.cerb5_licensing::quick_search.tpl"}
</div>
*}

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
<button type="button" id="btnPluginLibrarySync"><span class="cerb-sprite sprite-refresh"></span> Check for updates</button>
<div id="divPluginLibrarySync" style="display:none;font-size:18pt;text-align:center;padding:20px;margin:20px;background-color:rgb(232,242,255);"></div>
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}

<script type="text/javascript">
$('#btnPluginLibrarySync').click(function() {
	$out = $('#divPluginLibrarySync');
	$btn = $(this);
	$(this).hide();
	$out.html("Synchronizing... please wait").fadeIn();
	genericAjaxGet('','c=config&a=handleSectionAction&section=plugin_library&action=sync', function(json) {
		if(json.status == true) {
			$out.html("Success! Synchronized " + json.count + " plugins.");
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');			
			setTimeout(function() {
				$('#divPluginLibrarySync').fadeOut();
				$btn.fadeIn();
			}, 2500);
		} else {
			$out.html("Error! " + json.message);
			$btn.show();
		}
	});
});
</script>