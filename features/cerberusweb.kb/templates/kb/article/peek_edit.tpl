{$peek_context = CerberusContexts::CONTEXT_KB_ARTICLE}
{$peek_context_id = $model->id|default:0}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="kb">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if $peek_context_id}<input type="hidden" name="id" value="{$peek_context_id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="format" value="2">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="title" value="{$model->title|default:''}" style="width:99%;border:solid 1px rgb(180,180,180);" autofocus="autofocus"><br>

<div>
	<div class="cerb-code-editor-toolbar cerb-code-editor-toolbar--article">
		<button type="button" title="Bold" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
		<button type="button" title="Italics" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
		<button type="button" title="Link" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
		<button type="button" title="Image" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
		<button type="button" title="Heading" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--heading"><span class="glyphicons glyphicons-header"></span></button>
		<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
		<button type="button" title="Quote" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
		<button type="button" title="Code" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
		<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" title="Insert snippet" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--snippets"><span class="glyphicons glyphicons-notes-2"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" title="Preview" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
	</div>

	<textarea id="content" name="content" rows="10" cols="60">{$model->content|default:''}</textarea>
</div>

{$attachments = DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_KB_ARTICLE, $peek_context_id)}

<fieldset class="peek black cerb-attachments" style="margin-top:10px;">
	<legend>{'common.attachments'|devblocks_translate|capitalize}:</legend>

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

<fieldset class="peek black">
	<legend>{'common.categories'|devblocks_translate|capitalize}:</legend>

	<div style="overflow:auto;height:150px;border:solid 1px var(--cerb-color-background-contrast-180);background-color:var(--cerb-color-background);">
		{foreach from=$levels item=depth key=node_id}
			<label>
				<input type="checkbox" name="category_ids[]" value="{$node_id}" onchange="div=document.getElementById('kbTreeCat{$node_id}');div.style.background=(this.checked)?'var(--cerb-color-background-contrast-230)':'';" {if (empty($model) && $root_id==$node_id) || isset($article_categories.$node_id)}checked{/if}>
				<span style="padding-left:{math equation="(x-1)*10" x=$depth}px;{if !$depth}font-weight:bold;{/if}">{if $depth}<span class="glyphicons glyphicons-chevron-right" style="color:rgb(80,80,80);"></span>{else}<span class="glyphicons glyphicons-folder-closed" style="color:rgb(80,80,80);"></span>{/if} <span id="kbTreeCat{$node_id}" {if (empty($model) && $root_id==$node_id) || isset($article_categories.$node_id)}style="background-color:var(--cerb-color-background-contrast-230);"{/if}>{$categories.$node_id->name}</span></span>
			</label>
			<br>
		{/foreach}
	</div>
</fieldset>

{if !empty($custom_fields)}
	<fieldset class="peek black">
		<legend>{'common.properties'|devblocks_translate|capitalize}:</legend>
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
	</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$peek_context_id}


{if !empty($peek_context_id)}
<fieldset style="display:none;margin-top:10px;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this knowledgebase article?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($peek_context_id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'kb.common.knowledgebase_article'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;

		// Drag/drop attachments

		var $attachments = $popup.find('fieldset.cerb-attachments');
		$attachments.cerbAttachmentsDropZone();

		// Editor
		var $editor = $popup.find('textarea[name=content]')
			.cerbTextEditor()
			;

		var $editor_toolbar = $popup.find('.cerb-code-editor-toolbar--article')
			.cerbTextEditorToolbarMarkdown()
			;

		// Paste images

		$editor.cerbTextEditorInlineImagePaster({
			attachmentsContainer: $attachments,
			toolbar: $editor_toolbar
		})

		// Upload image
		$editor_toolbar.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$popup.find('button.chooser_file').triggerHandler(new_event);

			{literal}
			$editor.cerbTextEditor('insertText', '![inline-image]({{cerb_file_url(' + event.file_id + ',"' + event.file_name + '")}})');
			{/literal}
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
				formData.set('context_ids[cerberusweb.contexts.kb_article]', '{$article->id|default:0}');
				formData.set('context_ids[cerberusweb.contexts.worker]', '{$active_worker->id|default:0}');

				genericAjaxPost(formData, null, null, function(json) {
					// If the content has placeholders, use that popup instead
					if (json.has_prompts) {
						var $popup_paste = genericAjaxPopup('snippet_paste', 'c=profiles&a=invoke&module=snippet&action=getPrompts&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');

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
			formData.set('module', 'kb');
			formData.set('action', 'preview');
			formData.set('content', $editor.val());

			genericAjaxPopup(
				'preview_article',
				formData,
				'reuse',
				false
			);
		});

		// Editor
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
	});
});
</script>
