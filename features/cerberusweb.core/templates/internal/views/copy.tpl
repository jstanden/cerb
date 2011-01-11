<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmCopy{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewDoCopy">
<input type="hidden" name="view_id" value="{$view_id}">

<H2>Copy Worklist</H2>

You can copy this worklist into your own workspaces, allowing you to put your favorite information in a single place.<br>
<br>

<b>Worklist Name:</b><br>
<input type="text" name="list_title" value="{$view->name}" size="45">
<br>
<br>

<b>{'home.workspaces.worklist.add.to_workspace'|devblocks_translate}:</b><br>
<select name="workspace_id">
	<option value="">- new workspace: -</option>
	{if !empty($workspaces)}
	{foreach from=$workspaces item=workspace}
	<option value="{$workspace->id}">{$workspace->name}</option>
	{/foreach}
	{/if}
</select>
<input type="text" name="new_workspace" size="32" maxlength="32" value="">
<br>
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');$('#{$view_id}_tips').html('');" style=""><span class="cerb-sprite sprite-delete"></span> Do nothing</button><br>
</form>

<script type="text/javascript">
	$('#frmCopy{$view->id} SELECT[name=workspace_id]').change(function() {
		if('' == $(this).val()) {
			$(this).siblings('input:text[name=new_workspace]').show();
		} else {
			$(this).siblings('input:text[name=new_workspace]').val('').hide();
		}
	});
</script>
