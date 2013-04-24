<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" id="btnPluginLibrarySync"><span class="cerb-sprite sprite-refresh"></span> Download updates</button>
</form>

<div id="divPluginLibrarySync" style="clear:both;display:none;font-size:18pt;text-align:center;padding:20px;margin:20px;background-color:rgb(232,242,255);"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
$('#btnPluginLibrarySync').click(function() {
	$out = $('#divPluginLibrarySync');
	$btn = $(this);
	$btn.hide();
	$out.html("Synchronizing... please wait").fadeIn();
	genericAjaxGet('','c=config&a=handleSectionAction&section=plugin_library&action=sync', function(json) {
		if(json.status == true) {
			if(json.updated == 0) {
				$out.html("Success! All plugins are up to date.");
			} else {
				$out.html("Success! Downloaded updates for " + json.updated + " plugin" + (json.updated != 1 ? "s" : "") + ".");
			}
			
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');
			setTimeout(function() {
				$('#divPluginLibrarySync').fadeOut();
				$btn.fadeIn();
			}, 2500);
		} else {
			$out.html("Error! " + json.message);
			$btn.fadeIn();
		}
	});
});
</script>