{$peek_context = CerberusContexts::CONTEXT_EMAIL_SIGNATURE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
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

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" valign="top" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	{if $owners_menu}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
		</td>
	</tr>
	{/if}
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

<div style="margin-top:5px;">
	<b>When sending plaintext email:</b> (required)

	<div class="cerb-code-editor-toolbar cerb-code-editor-toolbar--text">
		<button type="button" title="Insert placeholder" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--placeholders"><span class="glyphicons glyphicons-sampler"></span></button>
		<div class="cerb-code-editor-toolbar-divider"></div>
		<button type="button" title="Preview" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
	</div>

	<textarea name="signature" class="cerb-code-editor-text placeholders" data-editor-mode="ace/mode/twig" data-editor-lines="15" data-editor-line-numbers="false" style="height:100px;width:98%;">{$model->signature}</textarea>
</div>

<div style="margin:5px 0;">
	<b>When sending HTML email:</b> (optional)

	<div class="cerb-code-editor-toolbar cerb-code-editor-toolbar--html">
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

	<textarea name="signature_html" class="cerb-code-editor-html placeholders" data-editor-mode="ace/mode/twig" data-editor-lines="15" data-editor-line-numbers="false" style="height:100px;width:98%;">{$model->signature_html}</textarea>
</div>

<fieldset class="peek">
	<legend>{'common.attachments'|devblocks_translate|capitalize}</legend>
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
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this email signature?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.signature'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		var $placeholder_menu = $popup.find('.menu');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Plaintext signature

		var $editor_text = $popup.find('.cerb-code-editor-text')
			.cerbCodeEditor()
			;

		var editor_text = ace.edit($editor_text.nextAll('pre.ace_editor').attr('id'));

		var $editor_toolbar_text = $popup.find('.cerb-code-editor-toolbar--text')
			.cerbCodeEditorToolbarMarkdown()
		;

		$editor_toolbar_text.find('.cerb-markdown-editor-toolbar-button--placeholders').on('click', function(e) {
			var $cursor = $editor_text.nextAll('pre.ace_editor').find('.ace_text-input');

			$placeholder_menu
				.insertAfter($editor_toolbar_text)
				.toggle()
				.position({
					my: 'left bottom',
					at: 'left top',
					of: $cursor
				})
			;

			editor_text.focus();
		});

		$editor_toolbar_text.find('.cerb-markdown-editor-toolbar-button--preview').on('click', function(e) {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'email_signature');
			formData.set('action', 'preview');
			formData.set('format', 'text');
			formData.set('signature', editor_text.getValue());

			genericAjaxPopup(
				'preview_sig',
				formData,
				'reuse',
				false
			);
		});

		// HTML signature

		var $editor_html = $popup.find('.cerb-code-editor-html')
			.cerbCodeEditor()
			;

		var editor_html = ace.edit($editor_html.nextAll('pre.ace_editor').attr('id'));

		var $editor_toolbar_html = $popup.find('.cerb-code-editor-toolbar--html')
			.cerbCodeEditorToolbarMarkdown()
			;

		$editor_toolbar_html.on('cerb-editor-toolbar-image-inserted', function(event) {
			event.stopPropagation();

			var new_event = $.Event('cerb-chooser-save', {
				labels: event.labels,
				values: event.values
			});

			$popup.find('button.chooser_file').triggerHandler(new_event);

			editor.insertSnippet('![Image](' + event.url + ')');
			editor.focus();
		});

		$editor_toolbar_html.find('.cerb-markdown-editor-toolbar-button--placeholders').on('click', function(e) {
			var $cursor = $editor_html.nextAll('pre.ace_editor').find('.ace_text-input');

			$placeholder_menu
				.insertAfter($editor_toolbar_html)
				.toggle()
				.position({
					my: 'left bottom',
					at: 'left top',
					of: $cursor
				})
			;

			editor_html.focus();
		});

		$editor_toolbar_html.find('.cerb-markdown-editor-toolbar-button--preview').on('click', function(e) {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'email_signature');
			formData.set('action', 'preview');
			formData.set('format', 'markdown');
			formData.set('signature', editor_html.getValue());

			genericAjaxPopup(
				'preview_sig',
				formData,
				'reuse',
				false
			);
		});

		// Owners
		{if $owners_menu}
		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
		});
		
		$owners_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$owners_menu.hide();
				
				// Build bubble
				
				var context_data = token.split(':');
				var $li = $('<li/>');
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				var $hidden = $('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				var $a = $('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});
		{/if}

		// Attachments

		$popup.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});

		// Quick insert token menu

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
