{if !empty($custom_fields)}
{$uniqid = uniqid()}
{if $tbody}
<tbody id="cfields{$uniqid}">
{else}
<table cellspacing="0" cellpadding="2" width="100%" id="cfields{$uniqid}">
{/if}
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
				<label><input type="checkbox" name="field_ids[]" value="{$f_id}" {if !is_null($custom_fields_expanded.$f_id)}checked="checked"{/if}> {$f->name}:</label>
				{else}
					<input type="hidden" name="field_ids[]" value="{$f_id}">
					{$f->name}:
				{/if}
			</td>
			<td width="99%">
				<div id="bulkOpts{$f_id}" style="display:{if $bulk && is_null($custom_fields_expanded.$f_id)}none{else}block{/if};">
				{if $f->type==Model_CustomField::TYPE_SINGLE_LINE}
					<input type="text" name="{$field_name}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id}">
				{elseif $f->type==Model_CustomField::TYPE_URL}
					<input type="text" name="{$field_name}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id}" class="url">
				{elseif $f->type==Model_CustomField::TYPE_LIST}
					<div data-cerb-record-editor-list>
						{foreach from=$custom_field_values.$f_id item=val}
						<div>
							<input type="text" name="{$field_name}[]" size="45" style="width:98%;" maxlength="255" value="{$val}">
						</div>
						{/foreach}
						<button type="button" class="multi-text-add" data-field-name="{$field_name}"><span class="glyphicons glyphicons-circle-plus"></span></button>
					</div>
				{elseif $f->type==Model_CustomField::TYPE_CURRENCY}
					{$currency = DAO_Currency::get($f->params.currency_id)}
					{$currency->symbol}
					<input type="text" name="{$field_name}" size="24" maxlength="64" value="{DevblocksPlatform::strFormatDecimal($custom_field_values.$f_id, $currency->decimal_at)}" class="currency">
					{$currency->code}
				{elseif $f->type==Model_CustomField::TYPE_DECIMAL}
					{$decimal_at = $f->params.decimal_at}
					<input type="text" name="{$field_name}" size="24" maxlength="64" value="{DevblocksPlatform::strFormatDecimal($custom_field_values.$f_id, $decimal_at)}" class="decimal">
				{elseif $f->type==Model_CustomField::TYPE_NUMBER}
					<input type="text" name="{$field_name}" size="45" style="width:98%;" maxlength="255" value="{$custom_field_values.$f_id}" class="number">
				{elseif $f->type==Model_CustomField::TYPE_MULTI_LINE}
					{if $f->params.format == 'markdown'}
						{$tabs_uniqid = uniqid('tabs')}
						<div data-cerb-record-editor-markdown-tabs>
							<ul>
								<li data-cerb-tab="editor"><a href="#{$tabs_uniqid}Editor">{'common.editor'|devblocks_translate|capitalize}</a></li>
								<li data-cerb-tab="preview"><a href="#{$tabs_uniqid}Preview">{'common.preview'|devblocks_translate|capitalize}</a></li>
							</ul>
							
							<div id="{$tabs_uniqid}Editor">
								<div class="cerb-code-editor-toolbar">
									<button type="button">
										{'common.format.markdown'|devblocks_translate|capitalize}
									</button>

									<div class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;">
										<button type="button" title="Bold (Ctrl+B)" data-cerb-key-binding="ctrl+b" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
										<button type="button" title="Italics (Ctrl+I)" data-cerb-key-binding="ctrl+i" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
										<button type="button" title="Link (Ctrl+K)" data-cerb-key-binding="ctrl+k" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
										<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
										<button type="button" title="Quote (Ctrl+Q)" data-cerb-key-binding="ctrl+q" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
										<button type="button" title="Code (Ctrl+O)" data-cerb-key-binding="ctrl+o" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
										<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
									</div>

									<div class="cerb-code-editor-toolbar-divider"></div>
								</div>
								<textarea name="{$field_name}" class="multi-lines multi-lines-markdown">{$custom_field_values.$f_id}</textarea>
							</div>
							
							<div id="{$tabs_uniqid}Preview" style="border:1px solid var(--cerb-color-background-contrast-200);background-color:var(--cerb-color-form-input-background);"></div>
						</div>
					{else}
						<textarea name="{$field_name}" class="multi-lines" rows="4" cols="50" style="width:98%;">{$custom_field_values.$f_id}</textarea>
					{/if}
				{elseif $f->type==Model_CustomField::TYPE_CHECKBOX}
					<label><input type="checkbox" name="{$field_name}" value="1" {if $custom_field_values.$f_id}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
				{elseif $f->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
					{if $bulk}
						{foreach from=$f->params.options item=opt}
							<select name="{$field_name}[]">
								<option value=""></option>
								<option value="+{$opt}" {if is_array($custom_field_values.$f_id) && (in_array($opt, $custom_field_values.$f_id) || in_array("+{$opt}", $custom_field_values.$f_id))}selected{/if}>set</option>
								<option value="-{$opt}" {if is_array($custom_field_values.$f_id) && in_array("-{$opt}", $custom_field_values.$f_id)}selected{/if}>unset</option>
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
							{$cf_link_labels = []}
							{$cf_link_values = []}
							{CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $custom_field_values.$f_id, $cf_link_labels, $cf_link_values, null, true)}
							<li><img src="{devblocks_url}c=avatars&context=worker&context_id={$custom_field_values.$f_id}{/devblocks_url}?v=" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;"> <input type="hidden" name="{$field_name}" value="{$custom_field_values.$f_id}">{$cf_link_values._label} <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
						{/if}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_LINK}
					<button type="button" class="chooser-cfield-link" data-field-name="{$field_name}" data-context="{$f->params.context}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $custom_field_values.$f_id}
							{$link_dict = DevblocksDictionaryDelegate::instance(['_context' => $f->params.context, 'id' => $custom_field_values.$f_id])}
							<li>
								<a href="javascript:;" class="peek-cfield-link no-underline" data-context="{$link_dict->_context}" data-context-id="{$link_dict->id}">{$link_dict->_label}</a>
								<input type="hidden" name="{$field_name}" value="{$link_dict->id}">
								<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
							</li>
						{/if}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_FILE}
					<button type="button" field_name="{$field_name}" class="chooser-cfield-file">{'common.upload'|devblocks_translate|lower}</button>
					
					<ul class="bubbles chooser-container">
					{if $custom_field_values.$f_id}
						{$file_id = $custom_field_values.$f_id}
						{$file = DAO_Attachment::get($file_id)}
						<li><input type="hidden" name="{$field_name}" value="{$file->id}"><a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" target="_blank" rel="noopener">{$file->name}</a> ({$file->storage_size|devblocks_prettybytes}) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
					{/if}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_FILES}
					<button type="button" field_name="{$field_name}" class="chooser-cfield-files">{'common.upload'|devblocks_translate|lower}</button>
					<ul class="bubbles chooser-container">
					{foreach from=$custom_field_values.$f_id item=file_id}
						{$file = DAO_Attachment::get($file_id)}
						<li><input type="hidden" name="{$field_name}[]" value="{$file->id}"><a href="{devblocks_url}c=files&id={$file->id}&file={$file->name|escape:'url'}{/devblocks_url}" target="_blank" rel="noopener">{$file->name}</a> ({$file->storage_size|devblocks_prettybytes}) <a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
					{/foreach}
					</ul>
				{elseif $f->type==Model_CustomField::TYPE_DATE}
					<input type="text" id="{$field_name}" name="{$field_name}" class="input_date" size="45" maxlength="255" value="{if !empty($custom_field_values.$f_id)}{if is_numeric($custom_field_values.$f_id)}{$custom_field_values.$f_id|devblocks_date}{else}{$custom_field_values.$f_id}{/if}{/if}">
				{else}
					{$extension = Extension_CustomField::get($f->type, true)}
					{if $extension}
						{$extension->renderEditable($f, $field_name, $custom_field_values.$f_id)}
					{/if}
				{/if}
				</div>
			</td>
		</tr>
	{/foreach}
{if $tbody}
</tbody>
{else}
</table>
{/if}

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
	$cfields.find('a.peek-cfield-link').cerbPeekTrigger();
	
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
	
	// List
	$cfields.find('[data-cerb-record-editor-list]').on('keydown', function(e) {
		e.stopPropagation();
		
		let $this = $(this);
		let $target = $(e.target);
		let key_code = (window.Event) ? e.which : e.keyCode;
		
		if(!$target.is('input:text') || $target.val().length > 0)
			return true;
		
		if(8 === key_code) {
			$target.parent().remove();
			$this.find('input:text').last().focus();
			return false;
		}
		
		return true;
	});
	
	$cfields.find('button.multi-text-add').click(function() {
		var $button = $(this);
		var field_name = $button.attr('data-field-name');
		var $input = $('<input type="text" size="45" style="width:98%;" maxlength="255" class="multi-text">')
			.attr('name', field_name + '[]')
			;
		var $div = $('<div/>').append($input);
		$div.insertBefore($button);
		$input.focus();
	});

	$cfields.find('[data-cerb-record-editor-markdown-tabs]').each(function() {
		let $tabs_container = $(this);
		
		$tabs_container.find('textarea')
			.cerbTextEditor()
		; 

		// Comment editor toolbar
		$tabs_container.find('.cerb-code-editor-toolbar')
			.cerbTextEditorToolbarMarkdown()
		;
		
		$tabs_container.tabs({
			beforeActivate: function(event, ui) {
				if(ui.newTab.attr('data-cerb-tab') !== 'preview')
					return;

				Devblocks.getSpinner().appendTo(ui.newPanel.html(''));

				var formData = new FormData();
				formData.set('c', 'ui');
				formData.set('a', 'markdownPreview');
				formData.set('content', $tabs_container.find('textarea.multi-lines-markdown').val());

				genericAjaxPost(formData, null, null, function(html) {
					ui.newPanel.html(html);
				});
			}
		});
	});
});
</script>
{/if}