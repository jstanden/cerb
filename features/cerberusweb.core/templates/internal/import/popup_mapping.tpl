{$type = $visit->get('import.last.type')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmImport">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worklists">
<input type="hidden" name="action" value="saveImport">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>Associate Fields with Import Columns</legend>

	<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><b><abbr title="Checking fields here will match the imported fields against existing records rather than setting new values.  Any existing records that match all the checked fields will have the other field values set on them.">Match</abbr></b></td>
		<td style="padding-left:10px;"><b>Field</b></td>
		<td style="padding-left:10px;"><b>Set value from file column</b></td>
	</tr>
	{foreach from=$keys item=key key=token}
	<tr>
		<td valign="top" align="center">
			<input type="checkbox" name="sync_dupes[]" value="{$token}" {if $key.force_match}checked="checked" disabled="disabled"{/if}>
		</td>
		<td style="padding-left:10px;" valign="top">
			<span style="{if $key.required}font-weight:bold;{/if}">{$key.label|capitalize}</span>
			<input type="hidden" name="field[]" value="{$token}">
		</td>
		<td style="padding-left:10px;" valign="top">
			<select name="column[]" onchange="$(this).nextAll('div.custom').css('display', ($(this).val() == 'custom') ? 'block' : 'none');" class="{if $key.required}required{/if}">
				<option value=""></option>
				{foreach from=$columns item=column key=pos name=columns}
					<option value="{$pos}">Column {$smarty.foreach.columns.iteration}: {$column|capitalize}</option>
				{/foreach}
				{if $key.type == Model_CustomField::TYPE_CHECKBOX}
					<option value="yes">{'common.yes'|devblocks_translate|lower}</option>
					<option value="no">{'common.no'|devblocks_translate|lower}</option>
				{elseif $key.type == Model_CustomField::TYPE_DATE}
					<option value="now">now</option>
				{elseif $key.type == Model_CustomField::TYPE_WORKER || $key.type == "ctx_{CerberusContexts::CONTEXT_WORKER}"}
					<option value="me">me</option>
				{/if}
				<option value="custom">custom value:</option>
			</select>
			<div class="custom" style="display:none;">
				<textarea cols="45" rows="2" style="width:100%;" name="column_custom[]"></textarea>
			</div>
			<label for="columns[]" style="display:none;"></label>
		</td>
	</tr>
	{/foreach}
	</table>
</fieldset>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.continue'|devblocks_translate|capitalize}</button>
	<button type="button" class="preview"><span class="glyphicons glyphicons-cogwheel"></span> {'common.preview'|devblocks_translate|capitalize}</button>
	<button type="button" class="cancel"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</div>

<div id="divImportPreview" style="margin:10px 0 0 0;border:1px solid rgb(230,230,230);padding:5px;height:200px;overflow-y:auto;display:none;"></div>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#frmImport');
	var $frm = $popup.find('FORM#frmImport');
	
 	$frm.find('button.submit').click(function() {
		Devblocks.clearAlerts();
		
 		$('#divImportPreview').show().text('Importing... please wait');
		
 		var $div = $(this).closest('div');
 		$div.fadeOut();
 		
 		// [TODO] This should allow error reporting via JSON
 		genericAjaxPost('frmImport', '', null, function(json) {
			$('#divImportPreview').hide().text('');
			$div.fadeIn();
			
			 if('object' != typeof json)
				 return;
			 
			 if(json.error) {
				 Devblocks.createAlertError(json.error);
				 return;
			 }
			 
 			genericAjaxGet('view{$view_id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}');
 			genericAjaxPopupDestroy('{$layer}');
 		});
 	});
 	
 	$frm.find('button.preview').click(function() {
		Devblocks.clearAlerts();
		
 		var $frm = $(this).closest('form');
 		
 		$('#divImportPreview').show().text('Loading...');

 		var formData = new FormData($frm[0]);
 		formData.set('c', 'internal');
 		formData.set('a', 'invoke');
 		formData.set('module', 'worklists');
 		formData.set('action', 'saveImport');
 		formData.set('context', '{$context}');
 		formData.set('is_preview', '1');

 		genericAjaxPost(formData, '', '', function(json) {
			$('#divImportPreview').hide().text('');
			
			if('object' != typeof json)
				return;
			
			if(json.hasOwnProperty('error')) {
				Devblocks.createAlertError(json.error);
				return;
			}
			 
			if(json.hasOwnProperty('preview_output')) {
				$('#divImportPreview').html(json.preview_output).fadeIn();
			}
 		});
 	});
 	
 	$frm.find('button.cancel').click(function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
 	});
 	
 	$frm.find('textarea').autosize();
 	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
	});
	
	$popup.one('dialogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
	
});
</script>