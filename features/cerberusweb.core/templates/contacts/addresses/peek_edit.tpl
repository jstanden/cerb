{$form_id = "formAddressPeek{uniqid()}"}
<form action="#" method="POST" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="address">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$address->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $address}
	{$contact = DAO_Contact::get($address->contact_id)}
	{$org = DAO_ContactOrg::get($address->contact_org_id)}
{/if}

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
	
		{if !$address}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.email'|devblocks_translate|capitalize}:</b> </td>
			<td width="100%">
				{if !empty($email)}
					<input type="hidden" name="email" value="{$email}">
					{$email}
				{else}
					<input type="text" name="email" style="width:98%;" value="{$email}" class="required email" autocomplete="off" spellcheck="false">
				{/if}
			</td>
		</tr>
		{else}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.email'|devblocks_translate|capitalize}:</b> </td>
			<td width="100%">
				{$address->email}
			</td>
		</tr>
		{/if}
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="middle" align="right"><b>{'common.organization'|devblocks_translate|capitalize}:</b> </td>
			<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-autocomplete="if-null" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $org}
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}"><input type="hidden" name="org_id" value="{$org->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}">{$org->name}</a></li>
						{/if}
					</ul>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="middle" align="right"><b>{'common.contact'|devblocks_translate|capitalize}:</b> </td>
			<td width="99%" valign="top">
					<button type="button" class="chooser-abstract" data-field-name="contact_id" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-single="true" {if $org}data-query="org.id:{$org->id}"{/if} data-autocomplete="if-null" data-create="if-null" data-create-defaults="email:{if $address}{$address->id}{elseif $email}{$email}{/if} {if $org}org:{$org->id}{/if}"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $contact}
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=contact&context_id={$contact->id}{/devblocks_url}?v={$contact->updated_at}"><input type="hidden" name="contact_id" value="{$contact->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$contact->id}">{$contact->getName()}</a></li>
						{/if}
					</ul>
					
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.options'|devblocks_translate|capitalize}:</b> </td>
			<td width="100%">
				<label><input type="checkbox" name="is_banned" value="1" title="Check this box if new messages from this email address should be rejected." {if $address->is_banned}checked="checked"{/if}> {'address.is_banned'|devblocks_translate|capitalize}</label>
				<label><input type="checkbox" name="is_defunct" value="1" title="Check this box if the email address is no longer active." {if $address->is_defunct}checked="checked"{/if}> {'address.is_defunct'|devblocks_translate|capitalize}</label>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ADDRESS context_id=$address->id}

<div class="status"></div>

{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#{$form_id}');
	var $chooser_org = $popup.find('button.chooser-abstract[data-field-name="org_id"]');
	var $chooser_contact = $popup.find('button.chooser-abstract[data-field-name="contact_id"]');
	
	$popup.one('popup_open',function(event,ui) {
		// Title
		$popup.dialog('option','title', "{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.email_address'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				// When the org changes, default the contact chooser filter
				if($(e.target).attr('data-field-name') == 'org_id') {
					var $bubble = $chooser_org.siblings('ul.chooser-container').find('> li:first input:hidden');
					var $button_create_contact = $chooser_contact.siblings('button.chooser-create');
					
					if($bubble.length > 0) {
						var org_id = $bubble.val();
						$chooser_contact.attr('data-query', 'org.id:' + org_id);
						
						// If there's a contact create button, change its defaults to the form contents
						$button_create_contact.attr('data-edit', '{if $address}email:{$address->id}{/if} org:' + org_id);
					}
					
				}
			})
			;
		
		// Peek triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
	});
});
</script>