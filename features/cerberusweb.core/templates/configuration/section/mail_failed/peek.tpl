<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupMailFailed">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_failed">
<input type="hidden" name="action" value="savePeekPopup">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<b>storage/mail/fail/{$filename}:</b>
<div>
	<textarea name="message_source" style="width:100%;height:250px;white-space:pre;word-wrap:normal;">{$message_source}</textarea>
</div>

<div class="output" style="display:none;"></div>

<div style="margin:10px 0px;">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.retry'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{'Failed Message'}");
		
		$this.find('textarea:first').focus();
		
		$this.find('button.submit').click(function(e) {
			var $frm = $(this).closest('form');
			
			// [TODO] Send to the import JSON function
			
			$frm.find('input:hidden[name=section]').val('mail_import');
			$frm.find('input:hidden[name=action]').val('parseMessageJson');
			
			genericAjaxPost($frm, '', '', function(json) {
				var $frm = $('#frmSetupMailFailed');
				var $txt = $frm.find('textarea[name=message_source]');
				var $output = $frm.find('div.output');
				
				// If successful, display a link to the new ticket
				if(undefined != json.status && true == json.status) {
					var message = '<b>Ticket created:</b> <a href="' + json.ticket_url + '">' + json._label + '</a>';
					Devblocks.showSuccess($output, message, false, true);
					
					// [TODO] Delete the underlying message
					
				// If an error, display it
				} else if(undefined != json.status && false == json.status) {
					var message = (undefined != json.log && json.log.length > 0) ? json.log : json.message;
					Devblocks.showError($output, message, false, true);
					
				}
			});
		});
		
		$this.find('button.delete').click(function(e) {
			var $frm = $(this).closest('form');

			$frm.find('input:hidden[name=section]').val('mail_failed');
			$frm.find('input:hidden[name=action]').val('savePeekPopup');
			
			// [TODO] Delete the message source
			genericAjaxPost($frm, '', 'c=config&a=handleSectionAction&section=mail_failed&action=savePeekPopup', function(json) {
				// [TODO] If successful, close the popup and reload the underlying view
			});
		});
	} );
</script>