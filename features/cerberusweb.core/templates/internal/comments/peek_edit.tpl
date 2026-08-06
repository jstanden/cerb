{$peek_context = CerberusContexts::CONTEXT_COMMENT}
{$peek_context_id = $model->id}
{$is_html = (!$model->id && !DAO_WorkerPref::get($active_worker->id,'comment_disable_formatting',0)) || $model->is_markdown}
{$target = $model->getTargetDictionary()}

{$form_id = uniqid('form')}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="comment">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="is_markdown" value="{if $is_html}1{else}0{/if}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{if $model->id}
			{$author = $model->getActorDictionary()}
			{if $author}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.author'|devblocks_translate|capitalize}</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					{if $author->_image_url}
					<img src="{$author->_image_url}" style="height:16px;width:16px;border-radius:16px;">
					{/if}
					<a class="cerb-peek-trigger no-underline" data-context="{$author->_context}" data-context-id="{$author->id}">{$author->_label}</a>
				</div>
			</div>
			{/if}
		{/if}

		{if $model->context}
			{if $target}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.on'|devblocks_translate|capitalize}</label>
				<input type="hidden" name="context" value="{$target->_context}">
				<input type="hidden" name="context_id" value="{$target->id}">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					{if $target->_image_url}
					<img src="{$target->_image_url}" style="height:16px;width:16px;border-radius:16px;">
					{/if}
					<a class="cerb-peek-trigger no-underline" data-context="{$target->_context}" data-context-id="{$target->id}">{$target->_label}</a>
				</div>
			</div>
			{/if}
		{/if}

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.comment'|devblocks_translate|capitalize}</label>

			{* Built-in formatting + markdown/plaintext toggle come from the editor; this host section (mention /
			   snippet / preview) and any worker-configured custom toolbar merge in after the formatting buttons. *}
			<ul class="cerb-ui-toolbar" data-cerb-editor-toolbar hidden>
				<li data-value="mention" data-icon="mention" title="Insert @mention"></li>
				<li data-value="snippets" data-icon="clipboard" title="Insert snippet"></li>
				<li></li>
				<li data-value="preview" data-icon="eye-open" title="Preview"></li>
			</ul>

			{if $toolbar_custom}
				<div data-cerb-toolbar class="cerb-comment-editor-subtoolbar-custom" hidden>
					{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_custom)}
				</div>
			{/if}

			<textarea name="comment" placeholder="{'comment.notify.at_mention'|devblocks_translate}">{$model->comment}</textarea>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.attachments'|devblocks_translate|capitalize}</label>
			<div class="cerb-comment-attachments">
				<div class="cerb-ui-file-upload" data-name="file_ids" data-multiple="1">
					{if !empty($attachments)}
						{foreach from=$attachments item=attachment name=attachments}
							<li data-file-id="{$attachment->id}" data-file-name="{$attachment->name}" data-file-size="{$attachment->storage_size}"></li>
						{/foreach}
					{/if}
				</div>
			</div>
		</div>

		{if $model->id}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.options'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle">
					<input type="checkbox" name="options[update_timestamp]" id="optUpdateTs_{$form_id}" value="1">
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label for="optUpdateTs_{$form_id}">Update the comment timestamp</label>
			</div>
		</div>
		{/if}
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
	</div>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="comment"}
{/if}

<div class="status"></div>

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
		$popup.dialog('option','title',"{'common.comment'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;

		// Attachments

		let fu = null;
		if(window.CerbUI && CerbUI.FileUpload)
			fu = new CerbUI.FileUpload($frm.find('.cerb-ui-file-upload')[0], { name: 'file_ids', multiple: true });

		// Editor (replaces the legacy cerbTextEditor stack) — built-in formatting toolbar + markdown/plaintext
		// toggle. The host section (mention/snippet/preview) and any worker-configured custom toolbar merge in.
		let editor_sections = [ $popup.find('[data-cerb-editor-toolbar]')[0] ];
		{if $toolbar_custom}
		let custom_toolbar_ul = $popup.find('.cerb-comment-editor-subtoolbar-custom ul.cerb-ui-toolbar')[0];
		if(custom_toolbar_ul) editor_sections.push(custom_toolbar_ul);
		{/if}

		let ed = new CerbUI.MarkdownEditor($popup.find('textarea[name=comment]')[0], {
			mode: {if $is_html}'markdown'{else}'plaintext'{/if},
			onAutocomplete: CerbUI.MarkdownEditor.mentionSource(),
			onImage: function(info) {
				if(fu) fu.add([{ id: info.file_id, name: info.file_name }]);
				if(ed._editorToolbar) ed._editorToolbar.setMode('markdown'); // a pasted image enables markdown
			},
			toolbar: {
				onMode: function(v) { $frm.find('input:hidden[name=is_markdown]').val(v === 'markdown' ? '1' : '0'); },
				sections: editor_sections,
				onAction: function(value, ed) {
					if(value === 'mention')  { ed.insertText('@'); ed.openAutocomplete(); return true; }
					if(value === 'snippets') { insertSnippet(); return true; }
					if(value === 'preview')  { previewComment(); return true; }
					return false; // bold/italic/… run their built-in
				}{if $toolbar_custom},
				// The worker-configured custom toolbar fires interactions through CerbUI.Toolbar (cerbBotTrigger).
				toolbarOpts: {
					caller: {
						name: 'cerb.toolbar.comment.editor',
						params: {
							{if $target}
							record_type: '{$target->_type}',
							record_id: '{$target->id}',
							{/if}
							selected_text: ''
						}
					},
					start: function(formData) {
						formData.set('caller[params][selected_text]', ed.getSelection());
						formData.set('caller[params][text]', ed.getValue());
					},
					done: function(e) {
						if(e.type !== 'cerb-interaction-done')
							return;

						if (e.eventData.exit === 'error') {
							// Show error
						} else if(e.eventData.exit === 'return') {
							Devblocks.interactionWorkerPostActions(e.eventData);

							if(e.eventData.return && e.eventData.return.snippet) {
								ed.replaceSelection(e.eventData.return.snippet);
								setTimeout(function() { ed.focus(); }, 25);
							}
						}
					}
				}{/if}
			}
		});

		let insertSnippet = function() {
			let context = 'cerberusweb.contexts.snippet';
			let chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&q=' + encodeURIComponent('type:[plaintext,comment]') + '&single=1&context=' + encodeURIComponent(context);

			let $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

			$chooser.on('chooser_save', function (event) {
				if (!event.values || 0 === event.values.length)
					return;

				let snippet_id = event.values[0];

				if (null == snippet_id)
					return;

				let formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'snippet');
				formData.set('action', 'paste');
				formData.set('id', snippet_id);
				formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id}');

				genericAjaxPost(formData, null, null, function (json) {
					// If the content has placeholders, use that popup instead
					if (json.has_prompts) {
						let $popup_paste = genericAjaxPopup('snippet_paste', 'c=profiles&a=invoke&module=snippet&action=getPrompts&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');

						$popup_paste.bind('snippet_paste', function (event) {
							if (null == event.text)
								return;

							ed.insertText(event.text);
						});

					} else {
						ed.insertText(json.text);
					}
				});
			});
		};

		let previewComment = function() {
			let formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'comment');
			formData.set('action', 'preview');
			formData.set('context', $frm.find('input:hidden[name=context]').val());
			formData.set('comment', ed.getValue());
			formData.set('is_markdown', $frm.find('input:hidden[name=is_markdown]').val());

			genericAjaxPopup('comment_preview', formData, 'reuse', false);
		};

		setTimeout(function() {
			ed.focus();
			// With pre-filled text (e.g. a reply seeded with "@mention "), drop the caret at the end
			let _end = ed.getValue().length;
			if(_end > 0) ed.setSelection(_end, _end);
		}, 100);

		{if $pref_keyboard_shortcuts}
			let $editor_input = $popup.find('textarea[name=comment]');

			// Save focus
			$editor_input.bind('keydown', 'ctrl+return meta+return alt+return', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$popup.find('button.save').focus();
			});

			// Save click
			$editor_input.bind('keydown', 'ctrl+shift+return meta+shift+return alt+shift+return', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$popup.find('button.save').click();
			});
		{/if}
	});
});
</script>
