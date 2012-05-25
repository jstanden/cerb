<form action="#" method="POST" id="formContactPeek" name="formContactPeek" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveContactPeek">
<input type="hidden" name="id" value="{$contact->id}">
{if empty($contact->id) && !empty($context)}
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{/if}
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		{$primary = $contact->getPrimaryAddress()}
		{if !empty($primary)}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.email')|capitalize}: </td>
			<td width="100%">
				{$primary->email}
			</td>
		</tr>
		{/if}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.password')|capitalize}: </td>
			<td width="100%">
				<input type="text" name="password" value="" style="width:98%;"><br>
				<i>(leave blank to remain unchanged)</i>
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="middle" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($contact->id)}
					<label><input type="checkbox" name="is_watcher" value="1"> {'common.watchers.add_me'|devblocks_translate}</label>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks(CerberusContexts::CONTEXT_CONTACT_PERSON, array($contact->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=CerberusContexts::CONTEXT_CONTACT_PERSON context_id=$contact->id full=true}
				{/if}
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

{if $active_worker->hasPriv('core.addybook.person.actions.delete')}
	<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'formContactPeek', '{$view_id}', false, 'contact_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
{else}
	<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
{/if}

<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		// Title
		$(this).dialog('option','title', 'Contact');
		$('#formContactPeek :input:text:first').focus();
	} );
</script>