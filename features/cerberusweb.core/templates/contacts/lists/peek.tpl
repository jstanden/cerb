<form action="#" method="POST" id="frmContactListPeek" name="frmContactListPeek" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveListPeek">
<input type="hidden" name="id" value="{$list->id}">
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset>
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.name')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="name" value="{$list->name|escape}" class="required" style="width:98%;">
			</td>
		</tr>
	</table>
</fieldset>

{*
{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}
*}

{if 1 || $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button name="submit" type="button" onclick="if($('#frmContactListPeek').validate().form()) { genericAjaxPopupPostCloseReloadView('peek','frmContactListPeek', '{$view_id}'); } "><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
{else}
	<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
{/if}

<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		// Title
		$(this).dialog('option','title', '{'Contact List'|devblocks_translate|escape:'quotes'}');
		// Form validation
	    $("#frmContactListPeek").validate();
		$('#frmContactListPeek :input:text:first').focus();
	} );
</script>