<h2>Incoming Mail</h2>

<form id="frmSetupMailIncoming" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_incoming">
<input type="hidden" name="action" value="saveJson">

<fieldset>
	<legend>{'common.settings'|devblocks_translate|capitalize}</legend>

	<b>Reply to All:</b><br>
	<label><input type="checkbox" name="parser_autoreq" value="1" {if $settings->get('cerberusweb.core','parser_autoreq')}checked{/if}> Send helpdesk replies to every recipient (To:/Cc:) on the original message.</label><br>
	<br>

	<b>Always exclude these addresses as recipients:</b><br>
	<textarea name="parser_autoreq_exclude" rows="4" cols="76">{$settings->get('cerberusweb.core','parser_autoreq_exclude')}</textarea><br>
	<i>(one address per line)</i> &nbsp;  
	<i>use * for wildcards, like: *@do-not-reply.com</i><br>
	<br>

	<b>Attachments:</b><br>
	<label><input type="checkbox" name="attachments_enabled" value="1" {if $settings->get('cerberusweb.core','attachments_enabled',CerberusSettingsDefaults::ATTACHMENTS_ENABLED)}checked{/if}> Allow Incoming Attachments</label><br>
	<br>
	
	<div style="padding-left:10px;">
		<b>Maximum Attachment Size:</b><br>
		<input type="text" name="attachments_max_size" value="{$settings->get('cerberusweb.core','attachments_max_size',CerberusSettingsDefaults::ATTACHMENTS_MAX_SIZE)}" size="5"> MB<br>
		<i>(attachments larger than this will be ignored)</i><br>
	</div>
</fieldset>

<fieldset>
	<legend>Default Ticket Mask Format</legend>
	
	<b>Mask:</b> (all uppercase; A-Z, 0-9, -)<br>
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
	<button type="button" class="tester"><span class="cerb-sprite sprite-gear"></span> {'common.test'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="status"></div>
<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>

</form>

<script type="text/javascript">
	$('#frmSetupMailIncoming BUTTON.submit')
		.click(function(e) {
			genericAjaxPost('frmSetupMailIncoming','',null,function(json) {
				$o = $.parseJSON(json);
				if(false == $o || false == $o.status) {
					Devblocks.showError('#frmSetupMailIncoming div.status',$o.error);
				} else {
					Devblocks.showSuccess('#frmSetupMailIncoming div.status','Settings saved!');
				}
			});
		})
	;
	$('#frmSetupMailIncoming BUTTON.tester')
	.click(function(e) {
		$this = $(this);
		$this.hide();

		$frm = $('#frmSetupMailIncoming');
		$action = $frm.find('input:hidden[name=action]');
		$action.val('testMask');
		
		genericAjaxPost('frmSetupMailIncoming','',null,function(json) {
			$o = $.parseJSON(json);
			if(false == $o || false == $o.status) {
				Devblocks.showError('#frmSetupMailIncoming div.status',$o.error, false);
			} else {
				Devblocks.showSuccess('#frmSetupMailIncoming div.status',$o.message,false);
			}
			
			$action.val('saveJson');
			$this.show();
			console.log(o);
		});
	})
	;	
</script>