<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmCopy{$view->id}" onsubmit="return false;">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<H2>{'worklist.copy.title'|devblocks_translate|capitalize}</H2>

{'worklist.copy.info'|devblocks_translate}<br>
<br>

<b>{'worklist.copy.new_list_title'|devblocks_translate}</b><br>
<input type="text" name="list_title" value="{$view->name}" size="45">
<br>
<br>

<b>{'worklist.copy.new_parent_page'|devblocks_translate}</b><br>
{$pages = DAO_WorkspacePage::getByWorker($active_worker)}
<select name="workspace_page_id">
	<option value=""></option>
	{foreach from=$pages item=page key=page_id}
	{if Context_WorkspacePage::isWriteableByActor($page, $active_worker)}
	<option value="{$page_id}">{$page->name}</option>
	{/if}
	{/foreach}
</select>
<select name="workspace_tab_id">
</select>
<select name="_workspace_tabs" style="display:none;visibility:hidden;">
	{$tabs = DAO_WorkspaceTab::getAll()}
	{foreach from=$tabs item=tab key=tab_id}
	<option value="{$tab_id}" page_id="{$tab->workspace_page_id}">{$tab->name}</option>
	{/foreach}
</select>
<br>
<br>

<button type="button" onclick="genericAjaxPost('frmCopy{$view->id}','view{$view->id}','c=internal&a=viewDoCopy');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
<button type="button" onclick="$('#{$view_id}_tips').hide().html('');" style=""><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button><br>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmCopy{$view->id}');
	$frm.find('SELECT[name=workspace_page_id]').change(function() {
		var $options = $frm.find('select[name=_workspace_tabs]');
		
		var $dest = $frm.find('select[name=workspace_tab_id]');
		$dest.find('option').remove();
		
		var page_id = $(this).val();
		
		$options.find('[page_id="' + page_id + '"]').clone().appendTo($dest);
	});
});
</script>
