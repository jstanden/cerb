{$peek_context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="html_template">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="100%" style="margin-bottom:10px;">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:100%;" autofocus="true">
		</td>
	</tr>
</table>

<div class="cerb-code-editor-toolbar cerb-code-editor-toolbar--html">
	<button type="button" title="Bold" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
	<button type="button" title="Italics" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
	<button type="button" title="Link" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
	<button type="button" title="Image" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
	<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
	<button type="button" title="Heading" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--heading"><span class="glyphicons glyphicons-header"></span></button>
	<button type="button" title="Quote" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
	<button type="button" title="Code" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
	<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
	<div class="cerb-code-editor-toolbar-divider"></div>
	<button type="button" title="Preview" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
</div>

<textarea name="content" class="cerb-code-editor-html placeholders" data-editor-mode="ace/mode/html" data-editor-lines="15">
{if $model->content}{$model->content}{else}&lt;div id="body"&gt;
{literal}{{message_body}}{/literal}
&lt;/div&gt;

&lt;style type="text/css"&gt;
#body {
	font-family: Arial, Verdana, sans-serif;
	font-size: 10pt;
}

a { 
	color: black;
}

blockquote {
	color: rgb(0, 128, 255);
	font-style: italic;
	margin-left: 0px;
	border-left: 1px solid rgb(0, 128, 255);
	padding-left: 5px;
}

blockquote a {
	color: rgb(0, 128, 255);
}
&lt;/style&gt;{/if}</textarea>
	
<fieldset class="peek black" style="margin-top:15px;">
	<legend>{'common.signature'|devblocks_translate|capitalize} ({'common.optional'|devblocks_translate|lower})</legend>

	<div class="cerb-code-editor-toolbar cerb-code-editor-toolbar--signature">
		<button type="button" title="Insert placeholder" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--placeholders"><span class="glyphicons glyphicons-sampler"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" title="Bold" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
		<button type="button" title="Italics" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
		<button type="button" title="Link" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
		<button type="button" title="Image" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
		<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
		<button type="button" title="Quote" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
		<button type="button" title="Code" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
		<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" title="Preview" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
	</div>

	{$types = $values._types}
	{function tree level=0}
		{foreach from=$keys item=data key=idx}
			{$type = $types.{$data->key}}
			{if is_array($data->children) && !empty($data->children)}
				<li {if $data->key}data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"{/if}>
					{if $data->key}
						<div style="font-weight:bold;">{$data->l|capitalize}</div>
					{else}
						<div>{$idx|capitalize}</div>
					{/if}
					<ul>
						{tree keys=$data->children level=$level+1}
					</ul>
				</li>
			{elseif $data->key}
				<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
			{/if}
		{/foreach}
	{/function}

	<ul class="menu cerb-float" style="width:250px;display:none;">
		{tree keys=$placeholders}
	</ul>

	<textarea name="signature" class="cerb-code-editor-html placeholders" data-editor-mode="ace/mode/twig" data-editor-lines="15" data-editor-line-numbers="false">{$model->signature}</textarea>
</fieldset>

<fieldset class="peek black">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>

	{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $model->id)}

	<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
	<ul class="chooser-container bubbles cerb-attachments-container">
		{if !empty($attachments)}
			{foreach from=$attachments item=attachment name=attachments}
				<li>
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ATTACHMENT}" data-context-id="{$attachment->id}">
						<b>{$attachment->name}</b>
						({$attachment->storage_size|devblocks_prettybytes}	-
						{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{'display.convo.unknown_format'|devblocks_translate|capitalize}{/if})
					</a>
					<input type="hidden" name="file_ids[]" value="{$attachment->id}">
					<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
				</li>
			{/foreach}
		{/if}
	</ul>
</fieldset>

{if !empty($custom_fields)}
	<fieldset class="peek black">
		<legend>{'common.custom_fields'|devblocks_translate}</legend>
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this email template?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.email_template'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		var $placeholder_menu = $popup.find('.menu');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		var $content = $popup.find('textarea[name=content]')
			.cerbCodeEditor()
			;

		var editor_content = ace.edit($content.nextAll('pre.ace_editor').attr('id'));

		var $content_toolbar = $popup.find('.cerb-code-editor-toolbar--html')
			.cerbCodeEditorToolbarHtml()
			;

		$content_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$popup.find('button.chooser_file').triggerHandler(new_event);

			editor.insertSnippet('<img src="' + event.url + '" alt="Image">');
			editor.focus();
		});

		$content_toolbar.find('.cerb-html-editor-toolbar-button--preview').on('click', function(e) {
			var formData = new FormData();
			formData.append('c', 'profiles');
			formData.append('a', 'handleSectionAction');
			formData.append('section', 'html_template');
			formData.append('action', 'preview');
			formData.append('template', editor_content.getValue());

			genericAjaxPopup(
				'preview_html_template',
				formData,
				'reuse',
				false
			);
		});

		var $signature = $popup.find('textarea[name=signature]')
			.cerbCodeEditor()
			;

		var editor_signature = ace.edit($signature.nextAll('pre.ace_editor').attr('id'));

		var $signature_toolbar = $popup.find('.cerb-code-editor-toolbar--signature')
			.cerbCodeEditorToolbarMarkdown()
			;

		$signature_toolbar.find('.cerb-markdown-editor-toolbar-button--placeholders').on('click', function(e) {
			var $editor = $signature.nextAll('pre.ace_editor');
			var $cursor = $editor.find('.ace_text-input');

			$placeholder_menu
				.toggle()
				.position({
					my: 'left bottom',
					at: 'left top',
					of: $cursor
				})
			;

			editor_signature.focus();
		});

		$signature_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$popup.find('button.chooser_file').triggerHandler(new_event);

			editor.insertSnippet('![Image](' + event.url + ')');
			editor.focus();
		});

		$signature_toolbar.find('.cerb-markdown-editor-toolbar-button--preview').on('click', function(e) {
			var formData = new FormData();
			formData.append('c', 'profiles');
			formData.append('a', 'handleSectionAction');
			formData.append('section', 'html_template');
			formData.append('action', 'previewSignature');
			formData.append('signature', editor_signature.getValue());

			genericAjaxPopup(
				'preview_html_template_sig',
				formData,
				'reuse',
				false
			);
		});

		// Peek triggers
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Attachments
		
		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		// Placeholders

		$placeholder_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');

				if(undefined == token || undefined == label)
					return;

				var $field = $placeholder_menu.siblings('pre.ace_editor');

				if($field.is('.ace_editor')) {
					var evt = new jQuery.Event('cerb.insertAtCursor');
					evt.content = '{literal}{{{/literal}' + token + '{literal}}}{/literal}';
					$field.trigger(evt);
				}

				$placeholder_menu.hide();
			}
		});

		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
