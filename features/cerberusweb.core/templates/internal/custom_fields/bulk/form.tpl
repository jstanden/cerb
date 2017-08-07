{if !empty($custom_fields)}
{$uniqid = uniqid()}
<table cellspacing="0" cellpadding="2" width="100%" id="cfields{$uniqid}">
	<!-- Custom Fields -->
	{foreach from=$custom_fields item=f key=f_id}
		{if !empty($field_wrapper)}
		{$field_name = "{$field_wrapper}[field_{$f_id}]"}
		{else}
		{$field_name = "field_{$f_id}"}
		{/if}
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				{if $bulk}
				<label><input type="checkbox" name="field_ids[]" value="{$f_id}"> {$f->name}:</label>
				{else}
					<input type="hidden" name="field_ids[]" value="{$f_id}">
					{if $f->type=='U'}
						{if !empty($custom_field_values.$f_id)}<a href="{$custom_field_values.$f_id}" target="_blank">{$f->name}</a>{else}{$f->name}{/if}:
					{else}
						{$f->name}:
					{/if}
				{/if}
			</td>
			<td width="99%">
				<div id="bulkOpts{$f_id}" style="display:{if $bulk}none{else}block{/if};">
				{if $f->type==Model_CustomField::TYPE_SINGLE_LINE}
					<input type="text" name="{$field_name}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id}">
				{elseif $f->type==Model_CustomField::TYPE_URL}
					<input type="text" name="{$field_name}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id}" class="url">
				{elseif $f->type==Model_CustomField::TYPE_LIST}
					<div>
						{foreach from=$custom_field_values.$f_id item=val}
						<div>
							<input type="text" name="{$field_name}[]" size="45" style="width:98%;" maxlength="255" value="{$val}">
						</div>
						{/foreach}
						<button type="button" class="multi-text-add" data-field-name="{$field_name}"><span class="glyphicons glyphicons-circle-plus"></span></button>
					</div>
				{elseif $f->type==Model_CustomField::TYPE_NUMBER}
					<input type="text" name="{$field_name}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id}" class="number">
				{elseif $f->type==Model_CustomField::TYPE_MULTI_LINE}
					<textarea name="{$field_name}" rows="4" cols="50" style="width:98%;">{$custom_field_values.$f_id}</textarea>
				{elseif $f->type==Model_CustomField::TYPE_CHECKBOX}
					<label><input type="checkbox" name="{$field_name}" value="1" {if $custom_field_values.$f_id}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				{elseif $f->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
					{if $bulk}
						{foreach from=$f->params.options item=opt}
							<select name="{$field_name}[]">
								<option value=""></option>
								<option value="+{$opt}">set</option>
								<option value="-{$opt}">unset</option>
							</select>
							{$opt}
							<br>
						{/foreach}
					{else}
						{foreach from=$f->params.options item=opt}
						<label><input type="checkbox" name="{$field_name}[]" value="{$opt}" {if isset($custom_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label><br>
						{/foreach}
					{/if}
				{elseif $f->type==Model_CustomField::TYPE_DROPDOWN}
					<select name="{$field_name}">
						<option value=""></option>
						{foreach from=$f->params.options item=opt}
						<option value="{$opt}" {if $opt==$custom_field_values.$f_id}selected="selected"{/if}>{$opt}</option>
						{/foreach}
					</select>
				{elseif $f->type==Model_CustomField::TYPE_WORKER}
					{if empty($workers)}
						{$workers = DAO_Worker::getAllActive()}
					{/if}
					
					<button type="button" class="chooser-cfield-worker" data-field-name="{$field_name}" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $custom_field_values.$f_id}
							{CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $custom_field_values.$f_id, $cf_link_labels, $cf_link_values, null, true)}
							<li><img src="{devblocks_url}c=avatars&context=worker&context_id={$custom_field_values.$f_id}{/devblocks_url}?v=" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;"> <input type="hidden" name="{$field_name}" value="{$custom_field_values.$f_id}">{$cf_link_values._label} <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
						{/if}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_LINK}
					<button type="button" class="chooser-cfield-link" data-field-name="{$field_name}" data-context="{$f->params.context}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $custom_field_values.$f_id}
							{CerberusContexts::getContext($f->params.context, $custom_field_values.$f_id, $cf_link_labels, $cf_link_values, null, true)}
							<li><input type="hidden" name="{$field_name}" value="{$custom_field_values.$f_id}">{$cf_link_values._label} <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
						{/if}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_FILE}
					<button type="button" field_name="{$field_name}" class="chooser-cfield-file">{'common.upload'|devblocks_translate|lower}</button>
					
					<ul class="bubbles chooser-container">
					{if $custom_field_values.$f_id}
						{$file_id = $custom_field_values.$f_id}
						{$file = DAO_Attachment::get($file_id)}
						<li><input type="hidden" name="{$field_name}" value="{$file->id}"><a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" target="_blank">{$file->name}</a> ({$file->storage_size|devblocks_prettybytes}) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
					{/if}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_FILES}
					<button type="button" field_name="{$field_name}" class="chooser-cfield-files">{'common.upload'|devblocks_translate|lower}</button>
					<ul class="bubbles chooser-container">
					{foreach from=$custom_field_values.$f_id item=file_id}
						{$file = DAO_Attachment::get($file_id)}
						<li><input type="hidden" name="{$field_name}[]" value="{$file->id}"><a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" target="_blank">{$file->name}</a> ({$file->storage_size|devblocks_prettybytes}) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
					{/foreach}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_DATE}
					<input type="text" id="{$field_name}" name="{$field_name}" class="input_date" size="45" maxlength="255" value="{if !empty($custom_field_values.$f_id)}{if is_numeric($custom_field_values.$f_id)}{$custom_field_values.$f_id|devblocks_date}{else}{$custom_field_values.$f_id}{/if}{/if}">
				{/if}
				</div>
			</td>
		</tr>
	{/foreach}
</table>

<script type="text/javascript">
$(function() {
	var $cfields = $('#cfields{$uniqid}');
	
	$cfields.find('input.input_date').cerbDateInputHelper();
	
	$cfields.find('input:checkbox[name="field_ids[]"]').change(function() {
		var $div = $('#bulkOpts' + $(this).val());
		
		if($(this).is(':checked')) {
			$div.show();
		} else {
			$div.hide();
		}
	});
	
	// Workers
	$cfields.find('button.chooser-cfield-worker').cerbChooserTrigger();
	
	// Links
	$cfields.find('button.chooser-cfield-link').cerbChooserTrigger();
	
	$cfields.find('button.chooser-cfield-file').each(function() {
		var options = {
			single: true,
		};
		ajax.chooserFile(this,$(this).attr('field_name'),options);
	});
	
	// Files
	$cfields.find('button.chooser-cfield-files').each(function() {
		ajax.chooserFile(this,$(this).attr('field_name'));
	});
	
	// Multi-text
	$cfields.find('button.multi-text-add').click(function() {
		var $button = $(this);
		var field_name = $button.attr('data-field-name');
		var $container = $button.closest('div');
		var $input = $('<input type="text" size="45" style="width:98%;" maxlength="255" class="multi-text">')
			.attr('name', field_name + '[]')
			;
		var $div = $('<div/>').append($input);
		$div.insertBefore($button);
	});
});
</script>
{/if}