<h2>Import Package</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupImportPackage" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="import_package">
<input type="hidden" name="action" value="importJson">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<p>
	View the <a href="https://cerb.ai/resources/packages/" target="_blank">library of pre-built workflow packages</a>.
</p>

<fieldset>
	<legend>Package</legend>
	
	<b>JSON:</b><br>
	<textarea name="json" data-editor-mode="ace/mode/json" rows="5" cols="45"></textarea>
	<br>
	
	<div class="prompts" style="margin-bottom:10px;"></div>
	
	<div class="status"></div>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupImportPackage');
	var $status = $frm.find('div.status');
	
	$frm.find('textarea')
		.cerbCodeEditor()
	;
	
	$frm.find('BUTTON.submit')
		.click(function(e) {
			$status.html('').hide();
			
			genericAjaxPost('frmSetupImportPackage','',null,function(json) {
				if(false == json.status && json.prompts) {
					$frm.find('div.prompts').html(json.prompts);
				} else if(null == json || false == json.status) {
					Devblocks.showError($status,json.error);
				} else {
					if(json.results_html) {
						var $html = $(json.results_html);
						$frm.html($html);
						$html.find('.cerb-peek-trigger').cerbPeekTrigger();
					} else {
						Devblocks.showSuccess($status,'Imported!');
					}
				}
			});
		})
	;
});
</script>
