<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Data Query Tester</div>
		<div class="cerb-ui-header--subtitle"></div>
	</div>
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmSetupDataQueryTester">
<fieldset>
	<legend>
		Run this data query:
		{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
	</legend>
	
	<textarea id="dataQueryEditor" name="data_query" data-editor-lines="14" spellcheck="false"></textarea>
	<br>
	
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-play"></span> {'common.run'|devblocks_translate|capitalize}</button>
	
	<div class="status" style="margin-top:10px;display:none;">
		<textarea id="dataQueryResultsEditor" spellcheck="false"></textarea>
	</div>
</fieldset>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frmSetupDataQueryTester');
	var $status = $frm.find('div.status');
	var $button = $frm.find('BUTTON.submit');
	var $spinner = Devblocks.getSpinner();

	Devblocks.formDisableSubmit($frm);
	
	const resultsEditor = new CerbUI.JsonEditor($frm.find('#dataQueryResultsEditor')[0], { readOnly: true, minLines: 5 });

	const dq = new CerbUI.DataQuery($frm.find('#dataQueryEditor')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true, // built-in Suggestions button (top strip)
	});

	$button
		.click(function(e) {
			e.stopPropagation();

			Devblocks.clearAlerts();

			$button.hide();
			$status.hide();
			$spinner.insertBefore($status);
			resultsEditor.setValue('');
			
			var formData = new FormData();
			formData.set('c', 'ui');
			formData.set('a', 'dataQuery');
			formData.set('q', dq.getValue());

			genericAjaxPost(formData, null, null, function(json) {
				$button.fadeIn();
				$spinner.detach();
				
				if(null == json || false == json.status) {
					Devblocks.createAlertError(json.error);
					
				} else {
					// Reveal the results panel BEFORE setValue so the editor autosizes against a laid-out
					// element — a display:none textarea reports scrollHeight 0 and would clamp to minLines.
					$status.show();
					resultsEditor.setValue(JSON.stringify(json, null, 2));
				}
			});
		})
	;
});
</script>
