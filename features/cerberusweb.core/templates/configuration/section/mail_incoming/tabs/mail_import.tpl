<form id="frmSetupMailImport" action="javascript:;" method="POST" onsubmit="return false;" enctype="multipart/form-data">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="mail_incoming">
<input type="hidden" name="action" value="parseMessageJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Paste a message source:</b>
<div>
	<textarea name="message_source" style="width:98%;height:250px;">{$message_source}</textarea>
</div>

<div class="output" style="display:none;"></div>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupMailImport');
	
	$frm.find('button.submit').click(function() {
		var $frm = $('#frmSetupMailImport');
		var $output = $frm.find('div.output');
		$output.hide().html('');
		
		genericAjaxPost($frm, null, null, function(json) {
			var $frm = $('#frmSetupMailImport');
			var $txt = $frm.find('textarea[name=message_source]');
			var $output = $frm.find('div.output');
			
			// If successful, display a link to the new ticket
			if(undefined !== json.status && true === json.status) {
				$txt.val('');
				$output.html('');
				
				if(undefined !== json.ticket_label && undefined !== json.ticket_url) {
					var $b = $('<b>Created: </b>');
					
					var $a = $('<a/>')
						.attr('href', json.ticket_url)
						.attr('target', '_blank')
						.attr('rel', 'noopener')
						.text(json.ticket_label)
					;

					Devblocks.showSuccess($output, '', false, true);
					$output.find('p').append($b).append($a);
				}
				
			// If an error, display it
			} else if(undefined !== json.status && false === json.status) {
				var message = (undefined !== json.log && json.log.length > 0) ? json.log : json.error;
				Devblocks.showError($output, message, false, true);
			}
		});
	});
});
</script>