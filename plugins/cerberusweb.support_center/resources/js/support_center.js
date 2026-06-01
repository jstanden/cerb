document.addEventListener('click', function(e) {
	// Navigate to a URL (replaces onclick="document.location='...'")
	const $href = e.target.closest('[data-sc-href]');
	if($href) {
		document.location = $href.getAttribute('data-sc-href');
		return;
	}

	// AJAX-replace a target element's contents (history worklist sort/paging)
	const $ajax = e.target.closest('[data-sc-ajax-url]');
	if($ajax) {
		ajaxHtmlGet($ajax.getAttribute('data-sc-ajax-target'), $ajax.getAttribute('data-sc-ajax-url'));
		return;
	}

	// History ticket: switch from the read-only view to the edit form
	const $edit = e.target.closest('[data-sc-edit]');
	if($edit) {
		document.querySelector('#history div.properties-view').style.display = 'none';
		document.querySelector('#history form.properties-edit').style.display = 'block';
		return;
	}

	// History ticket: cancel editing, switch back to the read-only view
	const $editCancel = e.target.closest('[data-sc-edit-cancel]');
	if($editCancel) {
		document.querySelector('#history form.properties-edit').style.display = 'none';
		document.querySelector('#history div.properties-view').style.display = 'block';
		return;
	}

	// History ticket: reveal the reply box and focus its textarea
	const $reply = e.target.closest('[data-sc-reply]');
	if($reply) {
		const $div = $reply.nextElementSibling;
		$div.style.display = 'block';
		$div.querySelector('textarea').focus();
		return;
	}

	// History ticket: hide the reply box
	const $replyCancel = e.target.closest('[data-sc-reply-cancel]');
	if($replyCancel) {
		$replyCancel.closest('div.reply').style.display = 'none';
		return;
	}
});

// Auto-submit a form when a tagged control changes (contact reason radios)
document.addEventListener('change', function(e) {
	const $submit = e.target.closest('[data-sc-submit]');
	if($submit && $submit.form)
		$submit.form.submit();
});
