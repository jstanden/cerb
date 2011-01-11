<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddWorklist">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doAddWorkspace">

<b>{'home.workspaces.worklist.name'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="name" value="" size="35" style="width:100%;"><br>
<br>

<b>{'home.workspaces.worklist.type'|devblocks_translate|capitalize}:</b><br>
<select name="context">
	{foreach from=$contexts item=mft key=mft_id}
	{if isset($mft->params['options'][0]['workspace'])}
	<option value="{$mft_id}">{$mft->name}</option>
	{/if}
	{/foreach}
</select><br>
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
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('dashboard.add_view')|capitalize}");
		
		$('#frmAddWorklist SELECT[name=workspace_id]').change(function() {
			if('' == $(this).val()) {
				$(this).siblings('input:text[name=new_workspace]').show();
			} else {
				$(this).siblings('input:text[name=new_workspace]').val('').hide();
			}
		});
	});
</script>

