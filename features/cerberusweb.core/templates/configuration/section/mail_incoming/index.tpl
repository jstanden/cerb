<h2>{'common.mail.incoming'|devblocks_translate|capitalize}</h2>

<div id="tabsSetupMailIncoming">
	<ul>
		<li data-alias="settings"><a href="#tabsSetupMailIncomingSettings">{'common.settings'|devblocks_translate|capitalize}</a></li>
		<li data-alias="mailboxes"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailboxes{/devblocks_url}">{'common.mailboxes'|devblocks_translate|capitalize}</a></li>
		<li data-alias="filtering"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailFiltering{/devblocks_url}">{'common.mail.filtering'|devblocks_translate|capitalize}</a></li>
		<li data-alias="routing"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailRouting{/devblocks_url}">{'common.mail.routing'|devblocks_translate|capitalize}</a></li>
		<li data-alias="html"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailHtml{/devblocks_url}">HTML</a></li>
		<li data-alias="import"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailImport{/devblocks_url}">{'common.import'|devblocks_translate|capitalize}</a></li>
		<li data-alias="failed"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailFailed{/devblocks_url}">Failed Messages</a></li>
		<li data-alias="relay"><a href="{devblocks_url}ajax.php?c=config&a=invoke&module=mail_incoming&action=renderTabMailRelay{/devblocks_url}">External Relay</a></li>
	</ul>
	
	<div id="tabsSetupMailIncomingSettings">
		<form id="frmSetupMailIncoming" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
		<input type="hidden" name="c" value="config">
		<input type="hidden" name="a" value="invoke">
		<input type="hidden" name="module" value="mail_incoming">
		<input type="hidden" name="action" value="saveSettingsJson">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
		
		<fieldset class="peek">
			<legend>{'common.settings'|devblocks_translate|capitalize}</legend>
			
			<b>By default, deliver new mail to:</b>
			<br>
			
			<div style="margin-left:10px;padding:5px;">
				<button type="button" class="chooser-abstract" data-field-name="default_group_id" data-context="{CerberusContexts::CONTEXT_GROUP}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$default_group = DAO_Group::getDefaultGroup()}
				
				<ul class="bubbles chooser-container">
					{if $default_group}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=group&context_id={$default_group->id}{/devblocks_url}?v={$default_group->updated_at}"><input type="hidden" name="default_group_id" value="{$default_group->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$default_group->id}">{$default_group->name}</a></li>
					{/if}
				</ul>
			</div>
		
			<b>Reply to All:</b><br>
			<div style="margin-left:10px;padding:5px;">
				<label><input type="checkbox" name="parser_autoreq" value="1" {if $settings->get('cerberusweb.core','parser_autoreq')}checked{/if}> Send helpdesk replies to every recipient (To:/Cc:) on the original message.</label><br>
			</div>
		
			<b>Always exclude these addresses as participants:</b><br>
			<div style="margin-left:10px;padding:5px;">
				<textarea name="parser_autoreq_exclude" rows="4" cols="76">{$settings->get('cerberusweb.core','parser_autoreq_exclude')}</textarea><br>
				<i>(one address per line)</i> &nbsp;  
				<i>use * for wildcards, like: *@do-not-reply.com</i><br>
			</div>
		
			<b>Attachments:</b><br>
			<div style="margin-left:10px;padding:5px;">
				<label><input type="checkbox" name="attachments_enabled" value="1" {if $settings->get('cerberusweb.core','attachments_enabled',CerberusSettingsDefaults::ATTACHMENTS_ENABLED)}checked{/if}> Allow incoming attachments</label><br>
				
				<div style="padding-left:10px;">
					<b>Maximum Attachment Size:</b><br>
					<input type="text" name="attachments_max_size" value="{$settings->get('cerberusweb.core','attachments_max_size',CerberusSettingsDefaults::ATTACHMENTS_MAX_SIZE)}" size="5"> MB<br>
					<i>(attachments larger than this will be ignored)</i><br>
				</div>
			</div>
		</fieldset>
		
		<fieldset class="peek">
			<legend>Default Ticket Mask Format</legend>
			
			<b>Mask:</b> (all uppercase; A-Z, 0-9, -)<br>
			
			<div style="margin-left:10px;padding:5px;">
				<input type="text" name="ticket_mask_format" value="{$settings->get('cerberusweb.core',CerberusSettings::TICKET_MASK_FORMAT,CerberusSettingsDefaults::TICKET_MASK_FORMAT)}" size="64"><br>
				{literal}
				<b>L</b> - letter, 
				<b>N</b> - number, 
				<b>C</b> - letter or number, 
				<b>Y</b> - year, 
				<b>M</b> - month, 
				<b>D</b> - day, 
				<b>{TEXT}</b> - literal text
				{/literal}
				<br>
				<button type="button" class="tester"><span class="glyphicons glyphicons-cogwheel"></span> {'common.test'|devblocks_translate|capitalize}</button>
			</div>
		</fieldset>
		
		<fieldset class="peek">
			<legend>Displaying HTML Messages</legend>
			
			<b>When the Tidy extension is enabled:</b>
			
			<div style="margin-left:10px;padding:5px;">
				<label><input type="checkbox" name="html_no_strip_microsoft" value="1" {if $settings->get('cerberusweb.core',CerberusSettings::HTML_NO_STRIP_MICROSOFT,CerberusSettingsDefaults::HTML_NO_STRIP_MICROSOFT)}checked="checked"{/if}> Don't clean Microsoft Office formatting</label>
			</div>
			
		</fieldset>
		
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		</form>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupMailIncoming');
	
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('tabsSetupMailIncoming', '{$tab}');
	
	var $mailTabs = $('#tabsSetupMailIncoming').tabs(tabOptions);
	
	$mailTabs.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	$mailTabs.find('.chooser-abstract')
		.cerbChooserTrigger()
		;
	
	$mailTabs.find('.cerb-code-editor')
		.cerbCodeEditor()
		;
	
	$mailTabs.find('.cerb-template-trigger')
		.cerbTemplateTrigger()
		;
	
	$mailTabs.find('BUTTON.submit')
		.click(function(e) {
			Devblocks.saveAjaxTabForm($frm);
		})
	;
	
	$('#tabsSetupMailIncomingSettings BUTTON.tester')
		.click(function(e) {
			var $button = $(this);

			var formData = new FormData($frm[0]);
			formData.set('c', 'config');
			formData.set('a', 'invoke');
			formData.set('module', 'mail_incoming');
			formData.set('action', 'testMask');

			genericAjaxPost(formData, null, null, function(json) {
				Devblocks.handleAjaxFormResponse($frm, json);
				$button.show();
			});
		})
	;
});
</script>