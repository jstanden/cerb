{$peek_context = CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="html_template">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">HTML</div>
	</div>

{* Host toolbar section for the ScriptingEditor: placeholder insert (submenu) + HTML formatting + preview.
   The editor builds + wires the strip; one onAction handles every click. *}
<ul class="cerb-ui-toolbar" data-cerb-html-toolbar hidden>
	<li data-icon="placeholders" title="Insert placeholder">
		<ul>
			<li data-token="{literal}{{message_body}}{/literal}">Message Body</li>
			<li data-token="{literal}{{message_id_header}}{/literal}">Message-Id Header</li>
			<li data-token="{literal}{{message_token}}{/literal}">Message Token</li>
			<li data-token="{literal}{{group_id}}{/literal}">Group ID</li>
			<li data-token="{literal}{{group__label}}{/literal}">Group Name</li>
			<li data-token="{literal}{{bucket_id}}{/literal}">Bucket ID</li>
			<li data-token="{literal}{{bucket__label}}{/literal}">Bucket Name</li>
		</ul>
	</li>
	<li></li>
	<li data-value="bold" data-icon="bold" title="Bold"></li>
	<li data-value="italic" data-icon="italic" title="Italics"></li>
	<li data-value="heading" data-icon="header" title="Heading"></li>
	<li data-value="link" data-icon="link" title="Link"></li>
	<li data-value="image" data-icon="picture" title="Image"></li>
	<li data-value="list" data-icon="list" title="List"></li>
	<li data-value="quote" data-icon="quote" title="Quote"></li>
	<li data-value="code" data-icon="embed" title="Code"></li>
	<li data-value="table" data-icon="table" title="Table"></li>
	<li></li>
	<li data-value="preview" data-icon="eye-open" title="Preview"></li>
</ul>

<textarea name="content" data-editor-lines="15" spellcheck="false">
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
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.signature'|devblocks_translate|capitalize} <small class="cerb-u-text-muted cerb-u-fw-400">({'common.optional'|devblocks_translate|lower})</small></div>
	</div>

	<div class="cerb-ui-record-chooser" id="signatureChooser_{$form_id}">
		{if $model}
			{$signature = $model->getSignatureRecord()}
			{if $signature}
				<li data-context-id="{$signature->id}" data-label="{$signature->name}"></li>
			{/if}
		{/if}
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.attachments'|devblocks_translate|capitalize}</div>
	</div>

	{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE, $model->id)}

	<div class="cerb-ui-file-upload" data-name="file_ids" data-multiple="1">
		{if !empty($attachments)}
			{foreach from=$attachments item=attachment name=attachments}
				<li data-file-id="{$attachment->id}" data-file-name="{$attachment->name}" data-file-size="{$attachment->storage_size}"></li>
			{/foreach}
		{/if}
	</div>
</div>

{if !empty($custom_fields)}
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
		</div>
		<div class="cerb-ui-form">
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		</div>
	</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="email template"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.email_template'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Record chooser
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#signatureChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}',
				name: 'signature_id',
				emptyIcon: 'signature',
				searchPlaceholder: "{'common.signature'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

		// Attachments

		var fu_attachments = null;
		if(window.CerbUI && CerbUI.FileUpload)
			fu_attachments = new CerbUI.FileUpload($popup.find('.cerb-ui-file-upload')[0], { name: 'file_ids', multiple: true });

		// Editor — ScriptingEditor (Twig/placeholder highlight + autocomplete; no HTML tag coloring). It has no
		// built-in toolbar, so the whole strip is our own host SECTION: placeholder insert + HTML formatting +
		// preview. One onAction routes every click; the formatting actions just wrap the selection in HTML tags.

		var insertTemplateImage = function(ed) {
			var $chooser = genericAjaxPopup('chooser', 'c=internal&a=invoke&module=records&action=chooserOpenFile&single=1', null, true, '750');
			$chooser.one('chooser_save', function(event) {
				var file_id = event.values[0];
				var file_label = event.labels[0];
				var file_name = file_label.substring(0, file_label.lastIndexOf(' ('));
				var url = document.location.protocol + '//' + document.location.host + DevblocksWebPath
					+ 'files/' + encodeURIComponent(file_id) + '/' + encodeURIComponent(file_name);
				if(fu_attachments) fu_attachments.add([{ id: file_id, name: file_name }]);
				ed.insertSnippet('<img src="' + url + '" alt="Image">');
				ed.focus();
			});
		};

		var previewTemplate = function(ed) {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'html_template');
			formData.set('action', 'preview');
			formData.set('template', ed.getValue());
			genericAjaxPopup('preview_html_template', formData, 'reuse', false);
		};

		var editor_content = null;
		var contentEl = $popup.find('textarea[name=content]')[0];
		if(contentEl && window.CerbUI && CerbUI.ScriptingEditor) {
			editor_content = new CerbUI.ScriptingEditor(contentEl, {
				toolbar: {
					sections: $popup.find('[data-cerb-html-toolbar]')[0] ? [ $popup.find('[data-cerb-html-toolbar]')[0] ] : [],
					onAction: function(value, ed, item, sourceLi) {
						switch(value) {
							case 'bold':    ed.wrapSelection('<b>', '</b>'); return true;
							case 'italic':  ed.wrapSelection('<i>', '</i>'); return true;
							case 'heading': ed.wrapSelection('<h1>', '</h1>'); return true;
							case 'quote':   ed.wrapSelection('<blockquote>', '</blockquote>'); return true;
							case 'code':    ed.wrapSelection('<code>', '</code>'); return true;
							case 'link':    ed.insertSnippet('<a href="https://example.com">' + (ed.getSelectedText() || 'link text') + '</a>'); ed.focus(); return true;
							case 'list':    ed.insertSnippet('<ul>\n  <li>' + (ed.getSelectedText() || 'item') + '</li>\n</ul>\n'); ed.focus(); return true;
							case 'table':   ed.insertSnippet('<table>\n  <tr><th>Column</th><th>Column</th></tr>\n  <tr><td>Value</td><td>Value</td></tr>\n</table>\n'); ed.focus(); return true;
							case 'image':   insertTemplateImage(ed); return true;
							case 'preview': previewTemplate(ed); return true;
						}
						// A placeholder-tree leaf carries data-token (no data-value) → insert that token.
						if(sourceLi && sourceLi.dataset && sourceLi.dataset.token) { ed.insertSnippet(sourceLi.dataset.token); ed.focus(); return true; }
						return false;
					}
				}
			});
		}

		// Peek triggers
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
	});
});
</script>
