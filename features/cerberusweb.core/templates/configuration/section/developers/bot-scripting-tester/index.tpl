<h2>Automation Scripting Tester</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupBotScriptingTester">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="bot_scripting_tester">
<input type="hidden" name="action" value="runScript">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>
		Run this script:
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/scripting/"}
	</legend>
	
	<textarea name="bot_script" data-editor-mode="ace/mode/twig" rows="5" cols="45" class="placeholders"></textarea>
	<br>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-play"></span> {'common.run'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="status" style="margin-top:10px;"></div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupBotScriptingTester');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = Devblocks.getSpinner();

	Devblocks.formDisableSubmit($frm);
	
	$frm.find('textarea')
		.cerbCodeEditor()
		;
	
	$button
		.click(function(e) {
			Devblocks.clearAlerts();
			
			$button.hide();
			$spinner.detach();
			$status.html('').append($spinner);
			
			genericAjaxPost('frmSetupBotScriptingTester','',null,function(json) {
				$button.fadeIn();
				$status.html('');
				
				if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);
					
				} else if (json.html) {
					$status.html(json.html);
					
				} else {
					Devblocks.createAlertError("An unknown error occurred.");
				}
			});
		})
	;
});
</script>
