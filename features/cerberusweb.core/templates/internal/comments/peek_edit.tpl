{$peek_context = CerberusContexts::CONTEXT_COMMENT}
{$peek_context_id = $model->id}
{$is_html = (!$model->id && !DAO_WorkerPref::get($active_worker->id,'comment_disable_formatting',0)) || $model->is_markdown}

{$form_id = uniqid('form')}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="comment">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="is_markdown" value="{if $is_html}1{else}0{/if}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-form">
	{if $model->id}
		{$author = $model->getActorDictionary()}
		{if $author}
		<div>
			<label>{'common.author'|devblocks_translate|capitalize}:</label>
			<ul class="bubbles">
				<li>
					{if $author->_image_url}
					<img src="{$author->_image_url}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
					{/if}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{$author->_context}" data-context-id="{$author->id}">{$author->_label}</a>
				</li>
			</ul>
		</div>
		{/if}
	{/if}
	
	{if $model->context}
		{$target = $model->getTargetDictionary()}
		{if $target}
		<div>
			<label>{'common.on'|devblocks_translate|capitalize}:</label>
			<input type="hidden" name="context" value="{$target->_context}">
			<input type="hidden" name="context_id" value="{$target->id}">
			<ul class="bubbles">
				<li>
					{if $target->_image_url}
					<img src="{$target->_image_url}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
					{/if}
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{$target->_context}" data-context-id="{$target->id}">{$target->_label}</a>
				</li>
			</ul>
		</div>
		{/if}
	{/if}

	<div>
		<label>
			{'common.comment'|devblocks_translate|capitalize}:
		</label>

		<div class="cerb-code-editor-toolbar">
			<button type="button" title="Toggle formatting" class="cerb-code-editor-toolbar-button cerb-editor-toolbar-button--formatting" data-format="{if $is_html}html{else}plaintext{/if}">{if $is_html}Formatting on{else}Formatting off{/if}</button>

			<div class="cerb-code-editor-subtoolbar-format-html" style="{if !$is_html}display:none;{else}display:inline-block;{/if}">
				<button type="button" title="Bold (Ctrl+B)" data-cerb-key-binding="ctrl+b" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
				<button type="button" title="Italics (Ctrl+I)" data-cerb-key-binding="ctrl+i" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
				<button type="button" title="Link (Ctrl+K)" data-cerb-key-binding="ctrl+k" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
				<button type="button" title="Image (Ctrl+M)" data-cerb-key-binding="ctrl+m" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
				<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
				<button type="button" title="Quote (Ctrl+Q)" data-cerb-key-binding="ctrl+q" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
				<button type="button" title="Code (Ctrl+O)" data-cerb-key-binding="ctrl+o" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
				<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
			</div>

			<div class="cerb-code-editor-toolbar-divider"></div>

			<button type="button" title="Insert @mention" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--mention"><span class="glyphicons glyphicons-user-add"></span></button>
			<button type="button" title="Insert snippet" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--snippets"><span class="glyphicons glyphicons-notes-2"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>

			<button type="button" title="Preview (Ctrl+Shift+P)" data-cerb-key-binding="ctrl+shift+p" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
		</div>

		<textarea name="comment" placeholder="{'comment.notify.at_mention'|devblocks_translate}">{$model->comment}</textarea>
	</div>

	<div>
		<label>
			{'common.attachments'|devblocks_translate|capitalize}:
		</label>

		<div class="cerb-comment-attachments">
			<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
			<ul class="chooser-container bubbles">
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
		</div>
	</div>
	
	{if $model->id}
	<div>
		<label>{'common.options'|devblocks_translate|capitalize}:</label>
		<div style="margin-left: 10px;">
			<label style="font-weight:normal;">
				<input type="checkbox" name="options[update_timestamp]" value="1"> Update the comment timestamp
			</label>
		</div>
	</div>
	{/if}
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this comment?
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
		$popup.dialog('option','title',"{'common.comment'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons

		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});

		// Drag/drop attachments

		var $attachments = $frm.find('.cerb-comment-attachments');
		$attachments.cerbAttachmentsDropZone();

		// Editor

		var $editor = $popup.find('textarea[name=comment]');

		$editor
			.cerbTextEditor()
			.cerbTextEditorAutocompleteComments()
			;

		// Comment editor toolbar
		var $editor_toolbar = $popup.find('.cerb-code-editor-toolbar')
			.cerbTextEditorToolbarMarkdown()
			;

		// Paste images
		$editor.cerbTextEditorInlineImagePaster({
			attachmentsContainer: $attachments,
			toolbar: $editor_toolbar
		})

		// Formatting
		$editor_toolbar.find('.cerb-editor-toolbar-button--formatting').on('click', function() {
			var $button = $(this);

			if('html' === $button.attr('data-format')) {
				$editor_toolbar.triggerHandler($.Event('cerb-editor-toolbar-formatting-set', { enabled: false }));
			} else {
				$editor_toolbar.triggerHandler($.Event('cerb-editor-toolbar-formatting-set', { enabled: true }));
			}
		});

		$editor_toolbar.on('cerb-editor-toolbar-formatting-set', function(e) {
			var $button = $editor_toolbar.find('.cerb-editor-toolbar-button--formatting');

			if(e.enabled) {
				$frm.find('input:hidden[name=is_markdown]').val('1');
				$button.attr('data-format', 'html');
				$button.text('Formatting on');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','inline-block');
			} else {
				$frm.find('input:hidden[name=is_markdown]').val('0');
				$button.attr('data-format', 'plaintext');
				$button.text('Formatting off');
				$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','none');
			}
		});

		// Upload image
		$editor_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$popup.find('button.chooser_file').triggerHandler(new_event);

			$editor.cerbTextEditor('insertText', '![Image](' + event.url + ')');
		});

		// Mention
		$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--mention').on('click', function () {
			var token = $editor.cerbTextEditor('getCurrentWord');

			if(token !== '@') {
				$editor.cerbTextEditor('insertText', '@');
			}

			$editor.autocomplete('search');
		});

		// Snippets
		$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--snippets').on('click', function () {
			var context = 'cerberusweb.contexts.snippet';
			var chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&q=' + encodeURIComponent('type:[plaintext,comment]') + '&single=1&context=' + encodeURIComponent(context);

			var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

			$chooser.on('chooser_save', function (event) {
				if (!event.values || 0 === event.values.length)
					return;

				var snippet_id = event.values[0];

				if (null == snippet_id)
					return;

				var formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'snippet');
				formData.set('action', 'paste');
				formData.set('id', snippet_id);
				formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id}');

				genericAjaxPost(formData, null, null, function (json) {
					// If the content has placeholders, use that popup instead
					if (json.has_custom_placeholders) {
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=profiles&a=invoke&module=snippet&action=getPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');

						$popup_paste.bind('snippet_paste', function (event) {
							if (null == event.text)
								return;

							$editor.cerbTextEditor('insertText', event.text);
						});

					} else {
						$editor.cerbTextEditor('insertText', json.text);
					}
				});
			});
		});

		// Preview
		$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--preview').on('click', function () {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'comment');
			formData.set('action', 'preview');
			formData.set('context', $frm.find('input:hidden[name=context]').val());
			formData.set('comment', $frm.find('textarea[name=comment]').val());
			formData.set('is_markdown', $frm.find('input:hidden[name=is_markdown]').val());

			genericAjaxPopup(
				'comment_preview',
				formData,
				'reuse',
				false
			);
		});

		setTimeout(function() {
			$editor.focus();
		}, 100);

		// Attachments

		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});

		{if $pref_keyboard_shortcuts}
			// Save focus
			$editor.bind('keydown', 'ctrl+return meta+return alt+return', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$popup.find('button.submit').focus();
			});

			// Save click
			$editor.bind('keydown', 'ctrl+shift+return meta+shift+return alt+shift+return', function(e) {
				e.preventDefault();
				e.stopPropagation();
				$popup.find('button.submit').click();
			});
		{/if}

		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
