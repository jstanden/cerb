{*
	Cerb UI custom-field renderer (NON-bulk). A responsive cerb-ui-form--row grid; one --field per
	custom field (ordered by pos). Checkbox→CerbUI.Toggle, Picklist→CerbUI.SelectMenu, Worker/Record
	Link→CerbUI.RecordChooser; other types keep their legacy markup/JS. POST contract is identical to
	custom_fields/bulk/form.tpl: a hidden field_ids[] per field + the value name field_{id} (or
	field_{id}[]), optionally wrapped as {$field_wrapper}[field_{id}].
*}
{if !empty($custom_fields)}
{$uniqid = uniqid()}
{$custom_field_values = $custom_field_values|default:[]}
{* Raw mode: values are form display strings (e.g. legacy-behavior action params, parsed by
   strParseDecimal at save time) rather than DB-scaled integers — render them verbatim *}
{$custom_field_values_raw = $custom_field_values_raw|default:false}
<div class="cerb-ui-form" id="cfields{$uniqid}">
	<div class="cerb-ui-form--row">
		{foreach from=$custom_fields item=f key=f_id}
			{if !empty($field_wrapper)}
				{$field_name = "{$field_wrapper}[field_{$f_id}]"}
			{else}
				{$field_name = "field_{$f_id}"}
			{/if}

			{if $f->type == Model_CustomField::TYPE_MULTI_LINE}
				{* Full width — gets its own row *}
				{$cf_style = 'flex:1 1 100%;min-width:0;'}
			{else}
				{* At most two across (the min-width forces a 1-column collapse when narrow) *}
				{$cf_style = 'flex:1 1 calc(50% - 0.5em);min-width:13em;'}
			{/if}

			<div class="cerb-ui-form--field" style="{$cf_style}">
				<label class="cerb-ui-form--label">{$f->name}</label>
				<input type="hidden" name="field_ids[]" value="{$f_id}">

				{if $f->type==Model_CustomField::TYPE_SINGLE_LINE}
					<label class="cerb-ui-form--control">
						<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-text"></span>
						<input type="text" name="{$field_name}" maxlength="255" value="{$custom_field_values.$f_id}">
					</label>

				{elseif $f->type==Model_CustomField::TYPE_URL}
					<label class="cerb-ui-form--control">
						<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-globe"></span>
						<input type="text" name="{$field_name}" maxlength="255" value="{$custom_field_values.$f_id}" class="url">
					</label>

				{elseif $f->type==Model_CustomField::TYPE_NUMBER}
					<label class="cerb-ui-form--control">
						<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-hash"></span>
						<input type="text" name="{$field_name}" maxlength="255" value="{$custom_field_values.$f_id}" class="number">
					</label>

				{elseif $f->type==Model_CustomField::TYPE_CURRENCY}
					{$currency = DAO_Currency::get($f->params.currency_id)}
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						<span>{$currency->symbol}</span>
						<input type="text" name="{$field_name}" maxlength="64" value="{if $custom_field_values_raw}{$custom_field_values.$f_id}{else}{DevblocksPlatform::strFormatDecimal($custom_field_values.$f_id, $currency->decimal_at)}{/if}" class="currency" style="flex:1 1 auto;min-width:0;">
						<span class="cerb-u-text-muted">{$currency->code}</span>
					</div>

				{elseif $f->type==Model_CustomField::TYPE_DECIMAL}
					{$decimal_at = $f->params.decimal_at}
					<label class="cerb-ui-form--control">
						<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-calculator"></span>
						<input type="text" name="{$field_name}" maxlength="64" value="{if $custom_field_values_raw}{$custom_field_values.$f_id}{else}{DevblocksPlatform::strFormatDecimal($custom_field_values.$f_id, $decimal_at)}{/if}" class="decimal">
					</label>

				{elseif $f->type==Model_CustomField::TYPE_DATE}
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						<input type="text" id="{$field_name}" name="{$field_name}" data-cerb-date-picker maxlength="255" style="flex:1 1 auto;min-width:0;" value="{if !empty($custom_field_values.$f_id)}{if is_numeric($custom_field_values.$f_id)}{$custom_field_values.$f_id|devblocks_date}{else}{$custom_field_values.$f_id}{/if}{/if}">
					</div>

				{elseif $f->type==Model_CustomField::TYPE_CHECKBOX}
					<label class="cerb-ui-toggle">
						<input type="checkbox" name="{$field_name}" value="1" {if $custom_field_values.$f_id}checked="checked"{/if}>
						<span class="cerb-ui-toggle--slider"></span>
					</label>

				{elseif $f->type==Model_CustomField::TYPE_DROPDOWN}
					<select name="{$field_name}" data-cerb-cfield-selectmenu>
						<option value="">{'common.none'|devblocks_translate|capitalize}</option>
						{foreach from=$f->params.options item=opt}
							<option value="{$opt}" {if $opt==$custom_field_values.$f_id}selected="selected"{/if}>{$opt}</option>
						{/foreach}
					</select>

				{elseif $f->type==Model_CustomField::TYPE_WORKER}
					<div class="cerb-ui-record-chooser" data-cerb-cfield-chooser data-context="worker" data-name="{$field_name}" data-empty-icon="user">
						{if $custom_field_values.$f_id}
							{$cf_worker_labels = []}
							{$cf_worker_values = []}
							{CerberusContexts::getContext(CerberusContexts::CONTEXT_WORKER, $custom_field_values.$f_id, $cf_worker_labels, $cf_worker_values, null, true)}
							<li data-context-id="{$custom_field_values.$f_id}" data-label="{$cf_worker_values._label}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$custom_field_values.$f_id}{/devblocks_url}?v="></li>
						{/if}
					</div>

				{elseif $f->type==Model_CustomField::TYPE_LINK}
					<div class="cerb-ui-record-chooser" data-cerb-cfield-chooser data-context="{$f->params.context}" data-name="{$field_name}" data-empty-icon="link">
						{if $custom_field_values.$f_id}
							{$link_dict = DevblocksDictionaryDelegate::instance(['_context' => $f->params.context, 'id' => $custom_field_values.$f_id])}
							<li data-context-id="{$link_dict->id}" data-label="{$link_dict->_label}" data-image="{$link_dict->_image_url}"></li>
						{/if}
					</div>

				{elseif $f->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
					<div class="cerb-ui-value-picker" data-cerb-cfield-valuepicker>
						{foreach from=$f->params.options item=opt}
							<label><input type="checkbox" name="{$field_name}[]" value="{$opt}" {if isset($custom_field_values.$f_id.$opt)}checked="checked"{/if}> {$opt}</label>
						{/foreach}
					</div>

				{elseif $f->type==Model_CustomField::TYPE_LIST}
					<div class="cerb-ui-tag-input" data-cerb-cfield-taginput data-name="{$field_name}">
						{foreach from=$custom_field_values.$f_id item=val}
							<input type="text" name="{$field_name}[]" maxlength="255" value="{$val}">
						{/foreach}
					</div>

				{elseif $f->type==Model_CustomField::TYPE_MULTI_LINE}
					{if $f->params.format == 'markdown'}
						{$tabs_uniqid = uniqid('tabs')}
						<div data-cerb-record-editor-markdown-tabs>
							<ul>
								<li data-cerb-tab="editor"><a href="#{$tabs_uniqid}Editor">{'common.editor'|devblocks_translate|capitalize}</a></li>
								<li data-cerb-tab="preview"><a href="#{$tabs_uniqid}Preview">{'common.preview'|devblocks_translate|capitalize}</a></li>
							</ul>

							<div id="{$tabs_uniqid}Editor">
								<textarea class="multi-lines-markdown" name="{$field_name}" spellcheck="true">{$custom_field_values.$f_id}</textarea>
							</div>

							<div id="{$tabs_uniqid}Preview" style="border:1px solid var(--cerb-color-background-contrast-200);background-color:var(--cerb-color-form-input-background);"></div>
						</div>
					{else}
						<textarea name="{$field_name}" class="multi-lines" rows="4" style="width:100%;">{$custom_field_values.$f_id}</textarea>
					{/if}

				{elseif $f->type==Model_CustomField::TYPE_FILE}
					<div class="cerb-ui-file-upload" data-cerb-cfield-file-upload data-name="{$field_name}">
						{if $custom_field_values.$f_id}
							{$file = DAO_Attachment::get($custom_field_values.$f_id)}
							{if $file}
								<li data-file-id="{$file->id}" data-file-name="{$file->name}" data-file-size="{$file->storage_size}"></li>
							{/if}
						{/if}
					</div>

				{elseif $f->type==Model_CustomField::TYPE_FILES}
					<div class="cerb-ui-file-upload" data-cerb-cfield-file-upload data-name="{$field_name}" data-multiple="1">
						{foreach from=$custom_field_values.$f_id item=file_id}
							{$file = DAO_Attachment::get($file_id)}
							{if $file}
								<li data-file-id="{$file->id}" data-file-name="{$file->name}" data-file-size="{$file->storage_size}"></li>
							{/if}
						{/foreach}
					</div>

				{else}
					{$extension = Extension_CustomField::get($f->type, true)}
					{if $extension}
						{$extension->renderEditable($f, $field_name, $custom_field_values.$f_id)}
					{/if}
				{/if}
			</div>
		{/foreach}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $cfields = $('#cfields{$uniqid}');

	// Dates
	$cfields.find('input[data-cerb-date-picker]').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });

	// Markdown editors
	$cfields.find('[data-cerb-record-editor-markdown-tabs]').each(function() {
		var $tabs_container = $(this);

		if(window.CerbUI && CerbUI.MarkdownEditor)
			$tabs_container.find('textarea.multi-lines-markdown').each(function() {
				new CerbUI.MarkdownEditor(this, { mode: 'markdown', toolbar: { mode: false } });
			});

		$tabs_container.find('> ul').each(function() {
			if(!(window.CerbUI && CerbUI.Tabs)) return;
			new CerbUI.Tabs(this, { onTabSelected: function(index, tab) {
				if(tab.li.getAttribute('data-cerb-tab') !== 'preview')
					return;

				var $panel = $(tab.panel);
				Devblocks.getSpinner().appendTo($panel.html(''));

				var formData = new FormData();
				formData.set('c', 'ui');
				formData.set('a', 'markdownPreview');
				formData.set('content', $tabs_container.find('textarea.multi-lines-markdown').val());

				genericAjaxPost(formData, null, null, function(html) {
					$panel.html(html);
				});
			} });
		});
	});

	// CerbUI components
	if(window.CerbUI) {
		if(CerbUI.Toggle)
			$cfields.find('label.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

		if(CerbUI.SelectMenu)
			$cfields.find('select[data-cerb-cfield-selectmenu]').each(function() { new CerbUI.SelectMenu(this); });

		if(CerbUI.RecordChooser) {
			$cfields.find('[data-cerb-cfield-chooser]').each(function() {
				new CerbUI.RecordChooser(this, {
					context: this.getAttribute('data-context'),
					name: this.getAttribute('data-name'),
					emptyIcon: this.getAttribute('data-empty-icon') || 'file'
				});
			});
		}

		if(CerbUI.ValuePicker)
			$cfields.find('[data-cerb-cfield-valuepicker]').each(function() { new CerbUI.ValuePicker(this); });

		if(CerbUI.TagInput)
			$cfields.find('[data-cerb-cfield-taginput]').each(function() { new CerbUI.TagInput(this, { placeholder: 'Items separated by a newline' } ); });

		if(CerbUI.FileUpload) {
			$cfields.find('[data-cerb-cfield-file-upload]').each(function() {
				new CerbUI.FileUpload(this, {
					name: this.getAttribute('data-name'),
					multiple: this.hasAttribute('data-multiple'),
					accept: this.getAttribute('data-accept') || '',
					maxSize: parseInt(this.getAttribute('data-max-size'), 10) || 0
				});
			});
		}
	}
});
</script>
{/if}
