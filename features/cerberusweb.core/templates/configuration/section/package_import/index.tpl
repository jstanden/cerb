<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Import Package</div>
		<div class="cerb-ui-header--subtitle">Quickly create a set of related records using a pre-built template</div>
	</div>
</div>


<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupImportPackage">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="package_import">
<input type="hidden" name="action" value="importJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<p>
	View the <a href="https://cerb.ai/resources/packages/" target="_blank" rel="noopener">library of pre-built workflow packages</a>.
</p>

<fieldset>
	<legend>Package</legend>
	
	<b>JSON:</b><br>
	<textarea id="setup-import-package-json" name="json" data-editor-lines="20" spellcheck="false"></textarea>
	<br>
	
	<div class="prompts" style="margin-bottom:10px;"></div>
	
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupImportPackage');

	Devblocks.formDisableSubmit($frm);
	
	new CerbUI.JsonEditor($frm.find('#setup-import-package-json')[0], { validate: true, minLines: 5 });

	$frm.find('BUTTON.submit')
		.click(function(e) {
			Devblocks.clearAlerts();
			
			genericAjaxPost('frmSetupImportPackage','',null,function(json) {
				if(false == json.status && json.prompts) {
					$frm.find('div.prompts').html(json.prompts);
				} else if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);
					
				} else {
					if(json.message) {
						Devblocks.createAlert(json.message,'note');
					}
					
					if(json.results_html) {
						var $html = $(json.results_html);
						$frm.html($html);
						$html.find('.cerb-peek-trigger').cerbPeekTrigger();
					}
				}
			});
		})
	;
});
</script>
