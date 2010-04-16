<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formGroupsPeek" name="formGroupsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveGroupsPanel">
<input type="hidden" name="group_id" value="{$group->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Name: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$group->name|escape}" autocomplete="off">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">Buckets: </td>
		<td width="100%">
			
		</td>
	</tr>
</table>

<button type="button" onclick="genericPanel.dialog('close');genericAjaxPost('formGroupsPeek', 'view{$view_id}')"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
 &nbsp; 
<a href="{devblocks_url}c=groups&a=config&id={$group->id}{/devblocks_url}">configuration</a>
<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"Groups");
	} );
</script>
