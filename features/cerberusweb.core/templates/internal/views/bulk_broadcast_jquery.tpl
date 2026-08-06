// Broadcast markdown editor (replaces the legacy cerbTextEditor stack) — built-in formatting toolbar +
// markdown/plaintext toggle. The host section (placeholders / snippet / signature / preview) merges in.
let $broadcast_frm = $popup.find('textarea[name=broadcast_message]').closest('form');

// Attachments
let fu = null;
if(window.CerbUI && CerbUI.FileUpload)
	fu = new CerbUI.FileUpload($popup.find('.cerb-ui-file-upload')[0], { name: 'broadcast_file_ids', multiple: true });

let ed = new CerbUI.MarkdownEditor($popup.find('.cerb-broadcast-editor')[0], {
	mode: ($broadcast_frm.find('input:hidden[name=broadcast_format]').val() === 'parsedown') ? 'markdown' : 'plaintext',
	scripting: true, // highlight + autocomplete the Twig broadcast placeholders
	onImage: function(info) {
		if(fu) fu.add([{ id: info.file_id, name: info.file_name }]);
		if(ed._editorToolbar) ed._editorToolbar.setMode('markdown'); // a pasted image enables markdown
	},
	toolbar: {
		onMode: function(v) { $broadcast_frm.find('input:hidden[name=broadcast_format]').val(v === 'markdown' ? 'parsedown' : ''); },
		sections: [ $popup.find('.cerb-broadcast-toolbar')[0] ],
		toolbarOpts: { selectableParents: true }, // placeholder branches are insertable (click=insert, hover=expand)
		onAction: function(value, ed, item, sourceLi) {
			if(value === 'snippets')  { insertBroadcastSnippet(); return true; }
			if(value === 'signature') { ed.insertText('#signature\n'); return true; }
			if(value === 'preview')   { previewBroadcast(); return true; }
			if(value && typeof ed[value] === 'function') return false; // built-in formatting
			// A placeholder-tree leaf — insert its token wrapped in Twig output braces
			if(sourceLi && sourceLi.dataset && sourceLi.dataset.token) {
				ed.insertText({literal}'{{'{/literal} + sourceLi.dataset.token + {literal}'}}'{/literal});
				return true;
			}
			return false;
		}
	}
});

let insertBroadcastSnippet = function() {
	let context = 'cerberusweb.contexts.snippet';
	let chooser_url = 'c=internal&a=invoke&module=records&action=chooserOpen&q=' + encodeURIComponent('type:[plaintext,ticket,worker]') + '&single=1&context=' + encodeURIComponent(context);

	let $chooser = genericAjaxPopup(Devblocks.uniqueId(), chooser_url, null, true, '90%');

	$chooser.on('chooser_save', function (event) {
		if (!event.values || 0 == event.values.length)
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
		formData.set('context_ids[{$context}]', '');

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

let previewBroadcast = function() {
	let formData = new FormData();
	formData.set('c', 'internal');
	formData.set('a', 'invoke');
	formData.set('module', 'worklists');
	formData.set('action', 'broadcastTest');
	formData.set('view_id', $broadcast_frm.find('input[name=view_id]').val());

	$broadcast_frm.find('input[name="broadcast_to[]"]:checked').each(function() {
		formData.append('broadcast_to[]', $(this).val());
	});

	$broadcast_frm.find('input[name=broadcast_subject]').each(function() {
		formData.set('broadcast_subject', $(this).val());
	});

	$broadcast_frm.find('select[name=broadcast_group_id]').each(function() {
		formData.set('broadcast_group_id', $(this).val());
	});

	$broadcast_frm.find('select[name=broadcast_bucket_id]').each(function() {
		formData.set('broadcast_bucket_id', $(this).val());
	});

	formData.set('broadcast_format', $broadcast_frm.find('input[name=broadcast_format]').val());
	formData.set('broadcast_message', ed.getValue());

	genericAjaxPopup('preview_broadcast', formData, 'reuse', false);
};

// Group -> bucket options
$broadcast_frm.find('select[name=broadcast_group_id]').on('change', function(e) {
	let $select = $(this);
	let group_id = $select.val();
	let $bucket_options = $select.siblings('select.broadcast-bucket-options').find('option');
	let $bucket = $select.siblings('select[name=broadcast_bucket_id]');

	$bucket.children().remove();

	$bucket_options.each(function() {
		let parent_id = $(this).attr('group_id');
		if(parent_id == '*' || parent_id == group_id)
			$(this).clone().appendTo($bucket);
	});

	$bucket.focus();
});

