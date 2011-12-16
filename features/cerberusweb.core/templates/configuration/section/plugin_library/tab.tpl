<div style="float:right;">
	<form action="#" method="post" id="frmPluginLibraryQuickSearch" onsubmit="return false;">
	<b>{$translate->_('common.quick_search')}</b> <select name="type">
		<option value="description">{$translate->_('dao.cerb_plugin.description')|capitalize}</option>
		<option value="author">{$translate->_('dao.cerb_plugin.author')|capitalize}</option>
	</select><input type="text" name="query" class="input_search" size="16"></button>
	</form>
</div>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" id="btnPluginLibrarySync"><span class="cerb-sprite sprite-refresh"></span> Check for updates</button>
</form>

<div id="divPluginLibrarySync" style="clear:both;display:none;font-size:18pt;text-align:center;padding:20px;margin:20px;background-color:rgb(232,242,255);"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}

<script type="text/javascript">
$('#btnPluginLibrarySync').click(function() {
	$out = $('#divPluginLibrarySync');
	$btn = $(this);
	$btn.hide();
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
			$btn.fadeIn();
		}
	});
});
$('#frmPluginLibraryQuickSearch INPUT:text[name=query]').keyup(function(e) {
	if(13 == e.keyCode || 10 == e.keyCode) {
		$(this).select();
		$frm = $(this).closest('form');
		
		switch($frm.find('select[name=type]').val()) {
			case 'author':
				ajax.viewAddFilter('{$view->id}','p_author','like',{ 'value':'*' + $(this).val() + '*' });
				break;
			default:	
			case 'description':
				ajax.viewAddFilter('{$view->id}','p_description','like',{ 'value':'*' + $(this).val() + '*' });
				break;
		}
	}
});
</script>