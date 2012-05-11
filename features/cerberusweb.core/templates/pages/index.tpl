<div style="margin-top:5px;"></div>

<div style="float:left;">
	<h2>Pages</h2>
</div>

<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

<div style="float:right;margin-right:10px;">
	<form action="{devblocks_url}{/devblocks_url}" id="frmWorkspacePages" method="POST">
	<input type="hidden" name="c" value="pages">
	<input type="hidden" name="a" value="">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Create Page</button>
	</form>
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

{include file="devblocks:cerberusweb.core::internal/views/view_workflow_keyboard_shortcuts.tpl" view=$view}

<script type="text/javascript">
$frm = $('#frmWorkspacePages');

$frm.find('button.add').click(function(e) {
	$popup=genericAjaxPopup('peek','c=internal&a=showEditWorkspacePage&id=0',null,true,'500');
	$popup.one('workspace_save', function(e) {
		genericAjaxGet('view{$view->id}', 'c=internal&a=viewRefresh&id={$view->id}');
	});
});
</script>