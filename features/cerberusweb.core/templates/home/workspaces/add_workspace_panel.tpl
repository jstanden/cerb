<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="home">
<input type="hidden" name="a" value="doAddWorkspace">

<b>{'home.workspaces.worklist.name'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="name" value="" size="35" style="width:100%;"><br>
<br>

<b>{'home.workspaces.worklist.type'|devblocks_translate|capitalize}:</b><br>
<select name="source">
	{foreach from=$sources item=mft key=mft_id}
	<option value="{$mft_id}">{$mft->name}</option>
	{/foreach}
</select><br>
<br>

<b>{'home.workspaces.worklist.add.to_workspace'|devblocks_translate}:</b><br>
{if !empty($workspaces)}
{'home.workspaces.worklist.add.existing'|devblocks_translate|capitalize}: <select name="workspace">
	{foreach from=$workspaces item=workspace}
	<option value="{$workspace|escape}">{$workspace}</option>
	{/foreach}
</select><br>
-{'common.or'|devblocks_translate|lower}-<br>
{/if}
{'home.workspaces.worklist.add.new'|devblocks_translate|capitalize}: <input type="text" name="new_workspace" size="32" maxlength="32" value=""><br>
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{$translate->_('dashboard.add_view')|capitalize|escape:'quotes'}");
	} );
</script>

