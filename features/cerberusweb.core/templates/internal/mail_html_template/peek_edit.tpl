{$peek_context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="html_template">
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
	<button type="button" class="cerb-code-editor-toolbar-button cerb-html-editor-toolbar-button--insert" title="Insert placeholder"><span class="glyphicons glyphicons-tags"></span></button>
	<ul class="cerb-float" style="display:none;">
		<li data-token="{literal}{{message_body}}{/literal}"><div><b>Message Body</b></div></li>
		<li data-token="{literal}{{message_id_header}}{/literal}"><div><b>Message-Id Header</b></div></li>
		<li data-token="{literal}{{message_token}}{/literal}"><div><b>Message Token</b></div></li>
		<li data-token="{literal}{{group_id}}{/literal}"><div><b>Group ID</b></div></li>
		<li data-token="{literal}{{group__label}}{/literal}"><div><b>Group Name</b></div></li>
		<li data-token="{literal}{{bucket_id}}{/literal}"><div><b>Bucket ID</b></div></li>
		<li data-token="{literal}{{bucket__label}}{/literal}"><div><b>Bucket Name</b></div></li>
	</ul>
	<div class="cerb-code-editor-toolbar-divider"></div>
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
	color: #646464;
	border-left: 5px solid;
	margin: 0 0 0 10px;
	padding: 0 0 0 10px;
}

blockquote a {
	color: rgb(0, 128, 255);
}

pre > code {
	display: block;
	overflow-x: auto;
	border: 1px solid #e8e8e8;
	background-color: #f6f2f0;
	padding: 10px;
}

p > code {
	background-color: #f6f2f0;
	border: 1px solid #e8e8e8;
	font-weight: bold;
	padding: 0.1em 0.2em;
	line-height: 1.75em;
}

h1, h2, h3, h4, h5, h6 {
	color: black;
	margin: 0 0 10px 0;
	font-weight: bold;
}

h1 { font-size: 2em; }
h2 { font-size: 1.85em; }
h3 { font-size: 1.75em; }
h4 { font-size: 1.5em; }
h5 { font-size: 1.25em; }
h6 { font-size: 1.1em; }

img {
	max-width: 100%;
}

ul, ol {
	padding-left: 2em;
}
&lt;/style&gt;{/if}</textarea>
	
<fieldset class="peek black" style="margin-top:15px;">
	<legend>{'common.signature'|devblocks_translate|capitalize} ({'common.optional'|devblocks_translate|lower})</legend>

	<button type="button" class="chooser-abstract" data-field-name="signature_id" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>

	{if $model}
		{$signature = $model->getSignatureRecord()}

		<ul class="bubbles chooser-container">
			{if $signature}
				<li><input type="hidden" name="signature_id" value="{$signature->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-context-id="{$signature->id}">{$signature->name}</a></li>
			{/if}
		</ul>
	{/if}
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
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
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

		// Choosers

		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
		;

		// Editors

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

			editor_content.insertSnippet('<img src="' + event.url + '" alt="Image">');
			editor_content.focus();
		});

		$content_toolbar.find('.cerb-html-editor-toolbar-button--preview').on('click', function(e) {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'html_template');
			formData.set('action', 'preview');
			formData.set('template', editor_content.getValue());

			genericAjaxPopup(
				'preview_html_template',
				formData,
				'reuse',
				false
			);
		});

		var $toolbar_button_insert = $content_toolbar.find('.cerb-html-editor-toolbar-button--insert');

		var $toolbar_button_insert_menu = $toolbar_button_insert.next('ul').menu({
			"select": function(e, $ui) {
				e.stopPropagation();
				$toolbar_button_insert_menu.hide();

				var data_token = $ui.item.attr('data-token');

				if (null == data_token)
					return;

				editor_content.insertSnippet(data_token);
			}
		});

		$toolbar_button_insert.on('click', function() {
			$toolbar_button_insert_menu.toggle();
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

	});
});
</script>
