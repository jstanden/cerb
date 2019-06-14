<div>
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false focus=true}
</div>

<form action="{devblocks_url}{/devblocks_url}" style="margin:5px 0px 5px 0px;">
	<button type="button" id="btnPluginLibrarySync"><span class="glyphicons glyphicons-refresh"></span></a> Download updates</button>
</form>

<div id="divPluginLibrarySync" style="clear:both;display:none;font-size:18pt;text-align:center;padding:20px;margin:20px;background-color:rgb(232,242,255);"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
$('#btnPluginLibrarySync').click(function() {
	$out = $('#divPluginLibrarySync');
	$btn = $(this);
	$btn.hide();
	$out.text("Synchronizing... please wait").fadeIn();
	genericAjaxGet('','c=config&a=handleSectionAction&section=plugin_library&action=sync', function(json) {
		if(json.status == true) {
			if(json.updated == 0) {
				$out.text("Success! All plugins are up to date.");
			} else {
				$out.text("Success! Downloaded updates for " + json.updated + " plugin" + (json.updated != 1 ? "s" : "") + ".");
			}
			
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');
			setTimeout(function() {
				$('#divPluginLibrarySync').fadeOut();
				$btn.fadeIn();
			}, 2500);
		} else {
			$out.text("Error! " + json.message);
			$btn.fadeIn();
		}
	});
});
</script>