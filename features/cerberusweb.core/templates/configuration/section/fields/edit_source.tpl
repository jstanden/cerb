<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="fields">
<input type="hidden" name="action" value="saveRecordType">
<input type="hidden" name="ext_id" value="{$context_manifest->id}">
<input type="hidden" name="submit" value="">

<fieldset>
	<legend>{$context_manifest->name} - {'common.custom_fields'|devblocks_translate|capitalize}</legend>

	<table cellspacing="2" cellpadding="1" border="0">
		<thead>
			<tr style="background-color:rgb(230,230,230);">
				<td align="center"></td>
				<td align="center"><input type="checkbox" class="check-all"></td>
				<td><b>Type</b></td>
				<td><b>Custom Field</b></td>
				<td><b>Options</b></td>
			</tr>
		</thead>
	{counter name=field_pos start=0 print=false}
	{foreach from=$fields item=f key=field_id name=fields}
		{assign var=type_code value=$f->type}
		<tbody class="sortable">
			<tr>
				<td valign="top" align="center"><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span></td>
				<td valign="top" align="center"><input type="checkbox" name="selected[]" value="{$field_id}"></td>
				<td valign="top">{$types.$type_code}</td>
				<td valign="top">
					<input type="hidden" name="ids[]" value="{$field_id}">
					<input type="text" name="names[]" value="{$f->name}" size="35" style="width:300;">
				</td>
				<td valign="top">
					{if $type_code == 'D' || $type_code == 'X'}
						<div>
							<textarea cols="35" rows="6" name="params[{$field_id}][options]" style="width:300;">{foreach from=$f->params.options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
							<div>
								(one option per line)
							</div>
						</div>
						</div>
					{/if}
				</td>
			</tr>
		</tbody>
	{/foreach}
	</table>
	<br>
	
	<!-- Add Custom Field -->
	<div style="margin-left:10px;">
		<b>Add new custom field:</b><br>
		<select name="add_type">
			{foreach from=$types item=type key=type_code}
			<option value="{$type_code}">{$type}</option>
			{/foreach}
		</select>
		 named 
		<input type="text" name="add_name" value="" size="45" maxlength="128">
	</div>
	<br>
	
	<fieldset class="delete" style="display:none;">
		<legend>Delete selected fields:</legend>
		<p>
			Are you sure you want to delete the selected custom fields and all of their data?
		</p>
		<button class="red" type="button" value="delete" onclick="$(this).closest('form').find('input:hidden[name=submit]').val('delete');genericAjaxPost('frmConfigFieldSource','frmConfigFieldSource');">{'common.yes'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
	</fieldset>
	
	{if !empty($fieldsets)}
	<fieldset class="move" style="display:none;">
		<legend>Move selected fields to custom fieldset:</legend>
		<p>
			<select name="move_to_fieldset_id">
				{foreach from=$fieldsets item=fieldset}
					{$owner_dict = $fieldset->getOwnerDictionary()}
					<option value="{$fieldset->id}">{$fieldset->name} ({$owner_dict->_label})</option>
				{/foreach}
			</select>
		</p>
		<button type="button" value="move" onclick="$(this).closest('form').find('input:hidden[name=submit]').val('move');genericAjaxPost('frmConfigFieldSource','frmConfigFieldSource');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>
		<button type="button" onclick="$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</fieldset>
	{/if}
	
	<div class="toolbar">
		<button id="frmConfigFieldSourceSubmit" type="button" onclick="$(this).closest('form').find('input:hidden[name=submit]').val('');genericAjaxPost('frmConfigFieldSource','frmConfigFieldSource');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		{if !empty($fieldsets)}<button id="frmConfigFieldSourceMove" type="button"><span class="cerb-sprite2 sprite-arrow-merge-090-left"></span> Move selected to fieldset</button>{/if}
		<button id="frmConfigFieldSourceDelete" type="button"><span class="cerb-sprite2 sprite-cross-circle"></span> Delete selected</button>
	</div>
</fieldset>

<script type="text/javascript">
var $frm = $('#frmConfigFieldSource');

$frm.find('input:checkbox.check-all').click(function() {
	var checked = $(this).is(':checked');
	
	var $checkboxes = $(this).closest('form').find('input:checkbox[name="selected[]"]');
	
	if(checked) {
		$checkboxes.attr('checked', 'checked');
	} else {
		$checkboxes.removeAttr('checked');
	}
});

$frm.find('table').sortable({ 
	items:'TBODY.sortable',
	helper: 'original',
	forceHelperSize: true,
	handle: 'span.ui-icon-arrowthick-2-n-s'
});

$frm.find('button#frmConfigFieldSourceDelete').click(function() {
	$(this).closest('.toolbar').fadeOut().siblings('fieldset.delete').fadeIn();
});

$frm.find('button#frmConfigFieldSourceMove').click(function() {
	$(this).closest('.toolbar').fadeOut().siblings('fieldset.move').fadeIn();
});

</script>