{assign var=type value=$visit->get('import.last.type')}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmImport">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doImport">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="view_id" value="{$view_id}">

<fieldset>
	<legend>Associate Fields with Import Columns</legend>

	<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td><b>Match</b></td>
		<td style="padding-left:10px;"><b>Field</b></td>
		<td style="padding-left:10px;"><b>Set value from file column</b></td>
	</tr>
	{foreach from=$keys item=key key=token}
	<tr>
		<td valign="top" align="center">
			<input type="checkbox" name="sync_dupes[]" value="{$token}" {if $key.force_match}checked="checked" disabled="disabled"{/if}>
		</td>
		<td style="padding-left:10px;" valign="top">
			<span style="{if $key.required}font-weight:bold;{/if}">{$key.label|capitalize}:</span>
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
			<label for="columns[]" class="error" style="display:none;"></label>
		</td>
	</tr>
	{/foreach}
	</table>
</fieldset>

<div class="buttons">
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.continue')|capitalize}</button>
	<button type="button" class="preview"><span class="cerb-sprite sprite-gear"></span> {$translate->_('common.preview')|capitalize}</button>
	<button type="button" class="cancel"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.cancel')|capitalize}</button>
</div>

<div id="divImportPreview" style="margin:10px 0 0 0;border:1px solid rgb(230,230,230);padding:5px;height:200px;overflow-y:auto;display:none;"></div>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#frmImport');
	$frm = $popup.find('FORM#frmImport');
	
 	$frm.find('button.submit').click(function(event) {
 		$frm = $(this).closest('form');
 		if(!$frm.validate().form())
 			return;

 		$('#divImportPreview').html('Importing... please wait');

 		$div = $(this).closest('div');
 		$div.fadeOut();
 		
 		genericAjaxPost('frmImport', '', null, function(o) {
 			genericAjaxGet('view{$view_id}','c=internal&a=viewRefresh&id={$view_id}');
 			genericAjaxPopupDestroy('{$layer}');
 		});
 	});
 	
 	$frm.find('button.preview').click(function() {
 		$frm = $(this).closest('form');
 		if(!$frm.validate().form())
			return;
 		
 		$('#divImportPreview').html('Loading...');
 		genericAjaxPost('frmImport', '', 'c=internal&a=doImport&context={$context}&is_preview=1', function(o) {
 			$('#divImportPreview').html(o).fadeIn();
 		});
 	});
 	
 	$frm.find('button.cancel').click(function() {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
 	});
 	
 	$frm.validate();
 	
 	$frm.find('textarea').elastic();
 	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title',"{'common.import'|devblocks_translate|capitalize}");
	});
	
	$popup.one('diagogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>