{$peek_context = CerberusContexts::CONTEXT_ADDRESS}
{$peek_context_id = $address->id}
{$form_id = "formAddressPeek{uniqid()}"}
<form action="#" method="POST" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="address">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$address->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $address}
	{$contact = DAO_Contact::get($address->contact_id)}
	{$org = DAO_ContactOrg::get($address->contact_org_id)}
{/if}

<table cellpadding="0" cellspacing="2" border="0" width="98%">

	{if !$address}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.email'|devblocks_translate|capitalize}:</b> </td>
		<td width="100%">
			{if !empty($email)}
				<input type="hidden" name="email" value="{$email}">
				{$email}
			{else}
				<input type="text" name="email" style="width:98%;" value="{$email}" class="required email" autocomplete="off" spellcheck="false" autofocus>
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
				<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				
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
				<button type="button" class="chooser-abstract" data-field-name="contact_id" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-single="true" {if $org}data-query="org.id:{$org->id}"{/if} data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null" data-create-defaults="email:{if $address}{$address->id}{elseif $email}{$email}{/if} {if $org}org:{$org->id}{/if}"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{if $contact}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=contact&context_id={$contact->id}{/devblocks_url}?v={$contact->updated_at}"><input type="hidden" name="contact_id" value="{$contact->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$contact->id}">{$contact->getName()}</a></li>
					{/if}
				</ul>
				
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<fieldset class="peek" style="margin-top:10px;">
	<legend>{'common.mail.filtering'|devblocks_translate|mb_ucfirst}</legend>
	
	<div style="margin-left:10px;">
		<label>
			<input type="checkbox" name="is_banned" value="1" {if $address->is_banned}checked="checked"{/if}>
			Reject incoming mail from this address
			({'address.is_banned'|devblocks_translate|lower})
		</label>
		<br>
		
		<label>
			<input type="checkbox" name="is_defunct" value="1" {if $address->is_defunct}checked="checked"{/if}>
			Reject outgoing mail to this address
			({'address.is_defunct'|devblocks_translate|lower})
		</label>
	</div>
</fieldset>

{if $active_worker->is_superuser}
<fieldset class="peek black cerb-email-type">
	<legend><label><input type="radio" name="type" value="transport" {if $address->mail_transport_id}checked="checked"{/if}> We send email from this address</label></legend>
	
	{if $active_worker->is_superuser}{/if}
	<div style="margin-left:20px;{if !$address->mail_transport_id}display:none;{/if}">
		<table cellpadding="0" cellspacing="2" border="0" width="98%">
			<tr>
				<td align="right" valign="middle" width="0%" nowrap="nowrap">
					<b>{'common.email_transport'|devblocks_translate|capitalize}: </b>
				</td>
				<td valign="middle" width="100%">
					<button type="button" class="chooser-abstract" data-field-name="mail_transport_id" data-context="{CerberusContexts::CONTEXT_MAIL_TRANSPORT}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					{$mail_transport = DAO_MailTransport::get($address->mail_transport_id)}
					
					<ul class="bubbles chooser-container">
					{if $mail_transport}
						<li><input type="hidden" name="mail_transport_id" value="{$mail_transport->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MAIL_TRANSPORT}" data-context-id="{$mail_transport->id}">{$mail_transport->name}</a></li>
					{/if}
					</ul>
				</td>
			</tr>
		</table>
	</div>
</fieldset>

<fieldset class="peek black cerb-email-type">
	<legend><label><input type="radio" name="type" value="worker" {if $address->worker_id}checked="checked"{/if}> This is a worker's personal email address</label></legend>
	
	<div style="margin-left:20px;{if !$address->worker_id}display:none;{/if}">
		<table cellpadding="0" cellspacing="2" border="0" width="98%">
			<tr>
				<td align="right" valign="middle" width="0%" nowrap="nowrap">
					<b>{'common.worker'|devblocks_translate|capitalize}: </b>
				</td>
				<td valign="middle" width="100%">
					<button type="button" class="chooser-abstract" data-field-name="worker_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="isDisabled:n" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					{$worker = DAO_Worker::get($address->worker_id)}
					
					<ul class="bubbles chooser-container">
					{if $worker}
						<li><input type="hidden" name="worker_id" value="{$worker->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{$worker->getName()}</a></li>
					{/if}
					</ul>
				</td>
			</tr>
		</table>
	</div>
</fieldset>

<fieldset class="peek black cerb-email-type">
	<legend><label><input type="radio" name="type" value="" {if !$address->mail_transport_id && !$address->worker_id}checked="checked"{/if}> None of the above</label></legend>
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$address->id}

<div class="status"></div>

{if (!$address && $active_worker->hasPriv("contexts.{$peek_context}.create")) 
	|| ($address && $active_worker->hasPriv("contexts.{$peek_context}.update"))}
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
		
		var $fieldsets_email_types = $popup.find('fieldset.cerb-email-type');
		
		// Radios
		{if $active_worker->is_superuser}
		$popup.find('input:radio[name=type]').on('change', function(e) {
			e.preventDefault();
			$fieldsets_email_types.find('> div').hide();
			$(this).closest('fieldset').find('> div').fadeIn();
		});
		{/if}
		
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
		
		// Search triggers
		$popup.find('.cerb-search-trigger').cerbSearchTrigger();
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>