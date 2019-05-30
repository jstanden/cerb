<h2>Data Query Tester</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupDataQueryTester" onsubmit="return false;">
<fieldset>
	<legend>
		Run this data query:
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
	</legend>
	
	<textarea name="data_query" data-editor-mode="ace/mode/cerb_query" rows="5" cols="45"></textarea>
	<br>
	
	<button type="button" class="submit"><span class="glyphicons glyphicons-play" style="color:rgb(0,180,0);"></span> {'common.run'|devblocks_translate|capitalize}</button>
	
	<div class="status" style="margin-top:10px;display:none;">
		<textarea class="cerb-data-query-results" data-editor-mode="ace/mode/json" rows="5" cols="45"></textarea>
	</div>
</fieldset>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmSetupDataQueryTester');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = $('<span class="cerb-ajax-spinner"/>');
	
	var $editor_results = 
		$frm.find('.cerb-data-query-results')
		.cerbCodeEditor()
		.nextAll('pre.ace_editor')
		;
	
	var $editor = $frm.find('textarea[name=data_query]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteDataQueries()
		.nextAll('pre.ace_editor')
		;
	
	var editor = ace.edit($editor.attr('id'));
	
	$button
		.click(function(e) {
			var editor_results = ace.edit($editor_results.attr('id'));
			
			Devblocks.clearAlerts();
			
			$button.hide();
			$status.hide();
			$spinner.insertBefore($status);
			editor_results.setValue('');
			
			var formData = new FormData();
			formData.append('q', editor.getValue());
			
			genericAjaxPost(formData, '', 'c=ui&a=dataQuery', function(json) {
				$button.fadeIn();
				$spinner.detach();
				
				if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);
					
				} else {
					editor_results.setReadOnly(true);
					editor_results.renderer.setOption('showLineNumbers', false);
					editor_results.setValue(JSON.stringify(json, null, 2));
					editor_results.clearSelection();
					$status.show();
				}
			});
		})
	;
});
</script>
