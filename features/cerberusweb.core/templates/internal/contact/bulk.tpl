<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="contact">
<input type="hidden" name="action" value="saveBulkPanel">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.title'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="title" size="45" value="" style="width:90%;">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.organization'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container"></ul>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.location'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="location" size="45" value="" style="width:90%;">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.gender'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="gender">
					<option value=""></option>
					<option value="M">{'common.gender.male'|devblocks_translate|capitalize}</option>
					<option value="F">{'common.gender.female'|devblocks_translate|capitalize}</option>
				</select>
				<button type="button" onclick="this.form.gender.selectedIndex = 1;">{'common.gender.male'|devblocks_translate|lower}</button>
				<button type="button" onclick="this.form.gender.selectedIndex = 2;">{'common.gender.female'|devblocks_translate|lower}</button>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CONTACT bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formBatchUpdate');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Submit
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', 'view{$view_id}', null, function() {
				genericAjaxPopupClose($popup);
			});
		});
	});
});
</script>