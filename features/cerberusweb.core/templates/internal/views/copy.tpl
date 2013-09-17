<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmCopy{$view->id}" onsubmit="return false;">
<input type="hidden" name="view_id" value="{$view_id}">

<H2>Copy Worklist</H2>

You can copy this worklist to other pages in order to build your ideal workspace.<br>
<br>

<b>Worklist title:</b><br>
<input type="text" name="list_title" value="{$view->name}" size="45">
<br>
<br>

<b>Copy to page:</b><br>
{$pages = DAO_WorkspacePage::getByWorker($active_worker)}
<select name="workspace_page_id">
	<option value=""></option>
	{foreach from=$pages item=page key=page_id}
	{if $page->isWriteableByWorker($active_worker)}
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

<button type="button" onclick="genericAjaxPost('frmCopy{$view->id}','view{$view->id}','c=internal&a=viewDoCopy');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');$('#{$view_id}_tips').html('');" style=""><span class="cerb-sprite2 sprite-cross-circle"></span> Do nothing</button><br>
</form>

<script type="text/javascript">
	$frm = $('#frmCopy{$view->id}');
	$frm.find('SELECT[name=workspace_page_id]').change(function() {
		$frm = $('#frmCopy{$view->id}');
		$options = $frm.find('select[name=_workspace_tabs]');
		
		$dest = $frm.find('select[name=workspace_tab_id]');
		$dest.find('option').remove();
		
		page_id = $(this).val();
		
		$options.find('[page_id="' + page_id + '"]').clone().appendTo($dest);
	});
</script>
