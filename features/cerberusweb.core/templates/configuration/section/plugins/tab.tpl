<div style="float:right;">
	<form action="#" method="post" id="frmPluginQuickSearch" onsubmit="return false;">
	<b>{$translate->_('common.quick_search')}</b> <select name="type">
		<option value="description">{$translate->_('dao.cerb_plugin.description')|capitalize}</option>
		<option value="author">{$translate->_('dao.cerb_plugin.author')|capitalize}</option>
	</select><input type="text" name="query" class="input_search" size="16"></button>
	</form>
</div>

<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
</form>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}

<script type="text/javascript">
$('#frmPluginQuickSearch INPUT:text[name=query]').keyup(function(e) {
	if(13 == e.keyCode || 10 == e.keyCode) {
		$(this).select();
		$frm = $(this).closest('form');
		
		switch($frm.find('select[name=type]').val()) {
			case 'author':
				ajax.viewAddFilter('{$view->id}','c_author','like',{ 'value':'*' + $(this).val() + '*' });
				break;
			default:	
			case 'description':
				ajax.viewAddFilter('{$view->id}','c_description','like',{ 'value':'*' + $(this).val() + '*' });
				break;
		}
	}
});
</script>