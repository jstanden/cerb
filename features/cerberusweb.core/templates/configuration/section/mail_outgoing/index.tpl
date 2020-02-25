<h2>{'common.mail.outgoing'|devblocks_translate|capitalize}</h2>

<div id="tabsSetupMailOutgoing">
	<ul>
		<li data-alias="transports"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_outgoing&action=renderTabMailTransports{/devblocks_url}">{'common.email_transports'|devblocks_translate|capitalize}</a></li>
		<li data-alias="senders"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_outgoing&action=renderTabMailSenderAddresses{/devblocks_url}">{'common.sender_addresses'|devblocks_translate|capitalize}</a></li>
		<li data-alias="settings"><a href="#tabsSetupMailOutgoingSettings">{'common.settings'|devblocks_translate|capitalize}</a></li>
		<li data-alias="templates"><a href="#tabsSetupMailOutgoingTemplates">Automated Email Templates</a></li>
		<li data-alias="queue"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_outgoing&action=renderTabMailQueue{/devblocks_url}">{'common.queue'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="tabsSetupMailOutgoingSettings">
		<form id="frmSetupMailOutgoingSettings" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
		<input type="hidden" name="c" value="config">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="mail_outgoing">
		<input type="hidden" name="action" value="saveSettingsJson">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<b>When not specified by a group, send mail from:</b>
		<br>
		
		<div style="margin-left:10px;padding:5px;">
			<button type="button" class="chooser-abstract" data-field-name="mail_default_from_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			{$default_sender = DAO_Address::getDefaultLocalAddress()}
			
			<ul class="bubbles chooser-container">
				{if $default_sender}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$default_sender->id}{/devblocks_url}?v={$default_sender->updated_at}"><input type="hidden" name="mail_default_from_id" value="{$default_sender->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$default_sender->id}">{$default_sender->getNameWithEmail()}</a></li>
				{/if}
			</ul>
		</div>
		
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		</form>
	</div>
	
	<div id="tabsSetupMailOutgoingTemplates">
		<form id="frmSetupMailOutgoingTemplates" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
		<input type="hidden" name="c" value="config">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="mail_outgoing">
		<input type="hidden" name="action" value="saveTemplatesJson">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
	
		{$template_placeholders = ['url' => 'Login URL']|json_encode}
		{$default_template = $default_templates.worker_invite}
		
		<fieldset>
			<legend>Worker new account invitation</legend>
			
			<b>{'common.send.from'|devblocks_translate}:</b>
			<br>
			
			<div style="margin-left:10px;padding:5px;">
				<button type="button" class="chooser-abstract" data-field-name="templates[worker_invite][send_from_id]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$send_from_id = $templates.worker_invite.send_from_id|default:$default_template.send_from_id}
				{$send_from = DAO_Address::get($send_from_id)}
				
				<ul class="bubbles chooser-container">
					{if $send_from}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$send_from->id}{/devblocks_url}?v={$send_from->updated_at}"><input type="hidden" name="templates[worker_invite][send_from_id]" value="{$send_from->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$send_from->id}">{$send_from->getNameWithEmail()}</a></li>
					{/if}
				</ul>
			</div>
			
			<b>{'common.send.as'|devblocks_translate}:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<textarea name="templates[worker_invite][send_as]" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-key-prefix="worker_" data-placeholders-json="{$template_placeholders}" placeholder="(e.g. Company Support)" style="width:100%;height:3em;">{$templates.worker_invite.send_as|default:$default_template.send_as}</textarea>
			</div>
			
			<b>{'message.header.subject'|devblocks_translate}:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<textarea name="templates[worker_invite][subject]" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-key-prefix="worker_" data-placeholders-json="{$template_placeholders}" placeholder="(e.g. &quot;Your account recovery confirmation code&quot;)" style="width:100%;height:3em;">{$templates.worker_invite.subject|default:$default_template.subject}</textarea>
			</div>
			
			<b>{'common.message'|devblocks_translate}:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<textarea name="templates[worker_invite][body]" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-key-prefix="worker_" data-placeholders-json="{$template_placeholders}" style="width:100%;height:10em;">{$templates.worker_invite.body|default:$default_template.body}</textarea>
			</div>
		</fieldset>
		
		{$template_placeholders = ['code' => 'Confirmation code', 'ip' => 'Client IP']|json_encode}
		{$default_template = $default_templates.worker_recover}
	
		<fieldset>
			<legend>Worker account recovery instructions</legend>
			
			<b>{'common.send.from'|devblocks_translate}:</b>
			<br>
			
			<div style="margin-left:10px;padding:5px;">
				<button type="button" class="chooser-abstract" data-field-name="templates[worker_recover][send_from_id]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$send_from_id = $templates.worker_recover.send_from_id|default:$default_template.send_from_id}
				{$send_from = DAO_Address::get($send_from_id)}
				
				<ul class="bubbles chooser-container">
					{if $send_from}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$send_from->id}{/devblocks_url}?v={$send_from->updated_at}"><input type="hidden" name="templates[worker_recover][send_from_id]" value="{$send_from->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$send_from->id}">{$send_from->getNameWithEmail()}</a></li>
					{/if}
				</ul>
			</div>
			
			<b>{'common.send.as'|devblocks_translate}:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<textarea name="templates[worker_recover][send_as]" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-key-prefix="worker_" data-placeholders-json="{$template_placeholders}" placeholder="(e.g. Company Support)" style="width:100%;height:3em;">{$templates.worker_recover.send_as|default:$default_template.send_as}</textarea>
			</div>
			
			<b>{'message.header.subject'|devblocks_translate}:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<textarea name="templates[worker_recover][subject]" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-key-prefix="worker_" data-placeholders-json="{$template_placeholders}" placeholder="(e.g. &quot;Your account recovery confirmation code&quot;)" style="width:100%;height:3em;">{$templates.worker_recover.subject|default:$default_template.subject}</textarea>
			</div>
			
			<b>{'common.message'|devblocks_translate}:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<textarea name="templates[worker_recover][body]" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-key-prefix="worker_" data-placeholders-json="{$template_placeholders}" style="width:100%;height:10em;">{$templates.worker_recover.body|default:$default_template.body}</textarea>
			</div>
		</fieldset>
		
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		</form>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('tabsSetupMailOutgoing', '{$tab}');
	
	var $tabs = $('#tabsSetupMailOutgoing').tabs(tabOptions);
	
	$tabs.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$tabs.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$tabs.find('.cerb-code-editor')
		.cerbCodeEditor()
		;
	
	$tabs.find('.cerb-template-trigger')
		.cerbTemplateTrigger()
		;
	
	$tabs.find('BUTTON.submit')
		.click(function(e) {
			var $button = $(this);
			var $button_form = $button.closest('form');
			
			Devblocks.saveAjaxTabForm($button_form);
		})
	;
});
</script>