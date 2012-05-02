<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formGroupsPeek" name="formGroupsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveGroupsPanel">
<input type="hidden" name="group_id" value="{$group->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>

	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Name: </td>
			<td width="100%">
				<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$group->name}" autocomplete="off">
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'formGroupsPeek','{$view_id}',false,'group_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>

{* &nbsp; 
<a href="{devblocks_url}c=groups&a=config&id={$group->id}{/devblocks_url}">configuration</a>
<br>
*}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Group");
		$('#formGroupsPeek :input:text:first').focus().select();
	} );
</script>
