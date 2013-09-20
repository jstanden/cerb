<h2>Import Message</h2>

<form id="frmSetupMailImport" action="javascript:;" method="POST" onsubmit="return false;" enctype="multipart/form-data">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_import">
<input type="hidden" name="action" value="parseMessageJson">

<b>Paste a message source:</b>
<div>
	<textarea name="message_source" style="width:98%;height:250px;">{$message_source}</textarea>
</div>

<div class="output" style="display:none;"></div>

<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.import'|devblocks_translate|capitalize}</button>

</form>

<script type="text/javascript">
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
		if(undefined != json.status && true == json.status) {
			$txt.val('');
			
			if(json.message)
				Devblocks.showSuccess($output, json.message, false, true);
			
		// If an error, display it
		} else if(undefined != json.status && false == json.status) {
			var message = (undefined != json.log && json.log.length > 0) ? json.log : json.message;
			Devblocks.showError($output, message, false, true);
			
		}
	});
});
</script>