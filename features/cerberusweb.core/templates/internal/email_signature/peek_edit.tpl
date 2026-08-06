{$peek_context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="email_signature">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{function tree level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
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
			<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
		{/if}
	{/foreach}
{/function}

<ul class="menu cerb-float" style="width:250px;display:none;">
	{tree keys=$placeholders}
</ul>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>
		{if $owners_menu}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			<div>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			</div>
		</div>
		{/if}
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">When sending plaintext email <small class="cerb-u-text-muted cerb-u-fw-400">({'common.required'|devblocks_translate})</small></div>
	</div>

	{* Host toolbar section (placeholder + preview) — the editor builds the strip and merges this in. *}
	<ul class="cerb-ui-toolbar" data-cerb-sig-text-toolbar hidden>
		<li data-value="placeholders" data-icon="placeholders" title="Insert placeholder"></li>
		<li></li>
		<li data-value="preview" data-icon="eye-open" title="Preview"></li>
	</ul>

	<textarea name="signature" data-editor-lines="15" spellcheck="false">{$model->signature}</textarea>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">When sending HTML email <small class="cerb-u-text-muted cerb-u-fw-400">({'common.optional'|devblocks_translate})</small></div>
	</div>

	{* Built-in formatting comes from the editor; this host section (placeholder + preview) merges in after it. *}
	<ul class="cerb-ui-toolbar" data-cerb-sig-html-toolbar hidden>
		<li data-value="placeholders" data-icon="placeholders" title="Insert placeholder"></li>
		<li></li>
		<li data-value="preview" data-icon="eye-open" title="Preview"></li>
	</ul>

	<textarea name="signature_html" spellcheck="false">{$model->signature_html}</textarea>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.attachments'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-file-upload" data-name="file_ids" data-multiple="1">
		{if !empty($attachments)}
			{foreach from=$attachments item=attachment name=attachments}
				<li data-file-id="{$attachment->id}" data-file-name="{$attachment->name}" data-file-size="{$attachment->storage_size}"></li>
			{/foreach}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="email signature"}
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
		$popup.dialog('option','title',"{'common.signature'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		var $placeholder_menu = $popup.find('.menu').hide();
		var activeEditor = null;   // which editor the placeholder menu inserts into (set on its toolbar click)

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		var previewSignature = function(format, value) {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'email_signature');
			formData.set('action', 'preview');
			formData.set('format', format);
			formData.set('signature', value);
			genericAjaxPopup('preview_sig', formData, 'reuse', false);
		};

		// Open the shared placeholder menu (anchored at the clicked toolbar button) for whichever editor was clicked.
		var openPlaceholders = function(ed, e) {
			activeEditor = ed;
			if(menu) menu.open((e && (e.currentTarget || e.target)) || ed.el);
			ed.focus();
		};

		// Attachments
		var fu = null;
		if(window.CerbUI && CerbUI.FileUpload)
			fu = new CerbUI.FileUpload($popup.find('.cerb-ui-file-upload')[0], { name: 'file_ids', multiple: true });

		// Plaintext signature — a ScriptingEditor (plaintext + Twig/KataScript). Sections-only toolbar (no built-in formatting).
		var edText = new CerbUI.ScriptingEditor($popup.find('textarea[name=signature]')[0], {
			minLines: 5,
			gutter: false,
			toolbar: {
				sections: [ $popup.find('[data-cerb-sig-text-toolbar]')[0] ],
				onAction: function(value, ed, item, sourceLi, e) {
					if(value === 'placeholders') { openPlaceholders(ed, e); return true; }
					if(value === 'preview')      { previewSignature('text', ed.getValue()); return true; }
					return false;
				}
			}
		});

		// HTML signature — a MarkdownEditor (Twig/KataScript scripting on). Built-in formatting + the placeholder/preview section.
		var edHtml = new CerbUI.MarkdownEditor($popup.find('textarea[name=signature_html]')[0], {
			mode: 'markdown',
			scripting: true,
			onImage: function(info) {
				// Also attach the inline image to this signature (mirrors the comment editor).
				if(fu) fu.add([{ id: info.file_id, name: info.file_name }]);
			},
			toolbar: {
				mode: false, // the HTML signature is always markdown — no plaintext toggle
				sections: [ $popup.find('[data-cerb-sig-html-toolbar]')[0] ],
				onAction: function(value, ed, item, sourceLi, e) {
					if(value === 'placeholders') { openPlaceholders(ed, e); return true; }
					if(value === 'preview')      { previewSignature('markdown', ed.getValue()); return true; }
					return false; // bold/italic/… run their built-in
				}
			}
		});

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Quick insert token menu

		var menu = new CerbUI.Menu($placeholder_menu[0], {
			selectableParents: true,
			filter: true,
			onSelect: function(li, src) {
				var token = src.getAttribute('data-token');
				var label = src.getAttribute('data-label');

				if(undefined == token || undefined == label)
					return;

				if(activeEditor)
					activeEditor.insertAtCursor('{literal}{{{/literal}' + token + '{literal}}}{/literal}');
			}
		});

	});
});
</script>
