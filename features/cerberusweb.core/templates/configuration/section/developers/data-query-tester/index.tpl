<h2>Data Query Tester</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupDataQueryTester" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="data_query_tester">
<input type="hidden" name="action" value="runQuery">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>
		Run this data query:
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
	</legend>
	
	<textarea name="data_query" data-editor-mode="ace/mode/text" rows="5" cols="45"></textarea>
	<br>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-play" style="color:rgb(0,180,0);"></span> {'common.run'|devblocks_translate|capitalize}</button>
	
	<div class="status" style="margin-top:10px;"></div>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupDataQueryTester');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = $('<span class="cerb-ajax-spinner"/>');
	
	$frm.find('textarea')
		.cerbCodeEditor()
		;
	
	$button
		.click(function(e) {
			$button.hide();
			$spinner.detach();
			$status.html('').append($spinner);
			
			genericAjaxPost('frmSetupDataQueryTester','',null,function(json) {
				$button.fadeIn();
				
				if(null == json || false == json.status) {
					Devblocks.showError($status,json.error);
					
				} else if (json.html) {
					$status.html(json.html);
					
				} else {
					Devblocks.showError($status,"An unknown error occurred.");
				}
			});
		})
	;
});
</script>
