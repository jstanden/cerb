var $attachments = $popup.find('.cerb-broadcast-attachments');

var $editor = $popup.find('textarea[name=broadcast_message]')
	.cerbTextEditor()
	.cerbTextEditorInlineImagePaster({
		attachmentsContainer: $attachments
	})
	;

var $frm = $editor.closest('form');

var $editor_toolbar = $popup.find('.cerb-code-editor-toolbar--broadcast')
	.cerbTextEditorToolbarMarkdown()
	;

// Formatting
$editor_toolbar.find('.cerb-reply-editor-toolbar-button--formatting').on('click', function() {
	var $button = $(this);

	if('html' === $button.attr('data-format')) {
		$frm.find('input:hidden[name=broadcast_format]').val('');
		$button.attr('data-format', 'plaintext');
		$button.text('Formatting off');
		$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','none');
	} else {
		$frm.find('input:hidden[name=broadcast_format]').val('parsedown');
		$button.attr('data-format', 'html');
		$button.text('Formatting on');
		$editor_toolbar.find('.cerb-code-editor-subtoolbar-format-html').css('display','inline-block');
	}
});

// Signature
$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--signature').on('click', function(e) {
	$editor.cerbTextEditor('insertText', "#signature\n");
});

// Placeholders
$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--placeholders').on('click', function(e) {
	$placeholder_menu
		.toggle()
	;

	// [TODO] Position at cursor

	$editor.focus();
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

// Snippets
$editor_toolbar.find('.cerb-markdown-editor-toolbar-button--snippets').on('click', function () {
	var context = 'cerberusweb.contexts.snippet';
	var chooser_url = 'c=internal&a=chooserOpen&q=' + encodeURIComponent('type:[plaintext,ticket,worker]') + '&single=1&context=' + encodeURIComponent(context);

	var $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

	$chooser.on('chooser_save', function (event) {
		if (!event.values || 0 == event.values.length)
			return;

		var snippet_id = event.values[0];

		if (null == snippet_id)
			return;

		// Now we need to read in each snippet as either 'raw' or 'parsed' via Ajax
		var url = 'c=internal&a=snippetPaste&id='
			+ encodeURIComponent(snippet_id)
			+ "&context_ids[cerberusweb.contexts.worker]={$active_worker->id}"
			+ "&context_ids[{$context}]="
			;

		genericAjaxGet('', url, function (json) {
			// If the content has placeholders, use that popup instead
			if (json.has_custom_placeholders) {
				var $popup_paste = genericAjaxPopup('snippet_paste', 'c=internal&a=snippetPlaceholders&id=' + encodeURIComponent(json.id) + '&context_id=' + encodeURIComponent(json.context_id), null, false, '50%');

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
	formData.append('c', 'internal');
	formData.append('a', 'viewBroadcastTest');
	formData.append('view_id', $frm.find('input[name=view_id]').val());

	$frm.find('input[name="broadcast_to[]"]:checked').each(function() {
		formData.append('broadcast_to[]', $(this).val());
	});

	$frm.find('input[name=broadcast_subject]').each(function() {
		formData.append('broadcast_subject', $(this).val());
	});

	$frm.find('select[name=broadcast_group_id]').each(function() {
		formData.append('broadcast_group_id', $(this).val());
	});

	$frm.find('select[name=broadcast_bucket_id]').each(function() {
		formData.append('broadcast_bucket_id', $(this).val());
	});

	formData.append('broadcast_format', $frm.find('input[name=broadcast_format]').val());
	formData.append('broadcast_message', $frm.find('textarea[name=broadcast_message]').val());

	genericAjaxPopup(
		'preview_broadcast',
		formData,
		'reuse',
		false
	);
});

// Placeholders

var $placeholder_menu = $popup.find('.menu').hide();

$placeholder_menu.menu({
	select: function(event, ui) {
		var token = ui.item.attr('data-token');
		var label = ui.item.attr('data-label');

		if(undefined == token || undefined == label)
			return;

		$editor.cerbTextEditor('insertText', '{literal}{{{/literal}' + token + '{literal}}}{/literal}');

		$placeholder_menu.hide();
	}
});

$frm.find('select[name=broadcast_group_id]').on('change', function(e) {
	var $select = $(this);
	var group_id = $select.val();
	var $bucket_options = $select.siblings('select.broadcast-bucket-options').find('option')
	var $bucket = $select.siblings('select[name=broadcast_bucket_id]');

	$bucket.children().remove();

	$bucket_options.each(function() {
		var parent_id = $(this).attr('group_id');
		if(parent_id == '*' || parent_id == group_id)
			$(this).clone().appendTo($bucket);
	});

	$bucket.focus();
});


$popup.find('button.chooser_file').each(function() {
	ajax.chooserFile(this,'broadcast_file_ids');
});