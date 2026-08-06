{$peek_context = CerberusContexts::CONTEXT_KB_ARTICLE}
{$peek_context_id = $model->id|default:0}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="kb">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if $peek_context_id}<input type="hidden" name="id" value="{$peek_context_id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="format" value="2">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
			<input type="text" name="title" value="{$model->title|default:''}" autofocus="autofocus">
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.content'|devblocks_translate|capitalize}</div>
	</div>

	{* Built-in formatting comes from the editor; this host section (snippet + preview) merges in after it. *}
	<ul class="cerb-ui-toolbar" data-cerb-editor-toolbar hidden>
		<li data-value="snippets" data-icon="clipboard" title="Insert snippet"></li>
		<li></li>
		<li data-value="preview" data-icon="eye-open" title="Preview"></li>
	</ul>

	<textarea name="content">{$model->content|default:''}</textarea>
</div>

{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $peek_context_id)}

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-attachments">
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

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.categories'|devblocks_translate|capitalize}</div>
	</div>

	<div class="cerb-ui-record-chooser" id="categoryChooser_{$form_id}" data-multiple="1">
		{foreach from=$article_categories key=cat_id item=cat}
			{if isset($categories.$cat_id)}
				<li data-context-id="{$cat_id}" data-label="{$categories.$cat_id->name}" data-eyebrow="{$category_eyebrows.$cat_id|default:''}"></li>
			{/if}
		{/foreach}
	</div>
</div>

{if !empty($custom_fields)}
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.properties'|devblocks_translate|capitalize}</div>
		</div>
		<div class="cerb-ui-form">
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		</div>
	</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$peek_context_id}


{if !empty($peek_context_id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="knowledgebase article"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($peek_context_id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'kb.common.knowledgebase_article'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Categories — multi-select record chooser (posts category_ids[]); eyebrow = ancestor path
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#categoryChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_KB_CATEGORY}',
				name: 'category_ids',
				multiple: true,
				emptyIcon: 'folder'
			});

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
			fu = new CerbUI.FileUpload($popup.find('.cerb-ui-file-upload')[0], { name: 'file_ids', multiple: true });

		// Editor — built-in formatting toolbar (no mode toggle; KB content is always markdown), with the
		// snippet/preview host section merged in. One onAction routes by value; formatting falls through.
		let ed = new CerbUI.MarkdownEditor($popup.find('textarea[name=content]')[0], {
			mode: 'markdown',
			minHeight: 200,
			maxHeight: 500,
			diffGutter: true,   // full-width body bands mark what changed since the article was opened

			// KB articles are Twig-rendered, so inline images insert a cerb_file_url(...) template expression.
			imageMarkdown: function(info) {
				{literal}return '![inline-image]({{cerb_file_url(' + info.file_id + ',"' + info.file_name + '")}})';{/literal}
			},
			onImage: function(info) {
				// Add the chosen/pasted file to the attachments component
				if(fu) fu.add([{ id: info.file_id, name: info.file_name }]);
			},
			toolbar: {
				mode: false,
				sections: [ $popup.find('[data-cerb-editor-toolbar]')[0] ],
				onAction: function(value, ed) {
					if(value === 'snippets') { insertSnippet(); return true; }
					if(value === 'preview')  { previewArticle(); return true; }
					return false;
				}
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
				formData.set('context_ids[cerberusweb.contexts.kb_article]', '{$article->id|default:0}');
				formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id|default:0}');

				genericAjaxPost(formData, null, null, function(json) {
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

		let previewArticle = function() {
			let formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'kb');
			formData.set('action', 'preview');
			formData.set('content', ed.getValue());

			genericAjaxPopup('preview_article', formData, 'reuse', false);
		};
	});
});
</script>
