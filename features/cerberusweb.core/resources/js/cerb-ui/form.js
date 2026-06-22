/*
 * CerbUI.Form — behavior helpers for the jQuery → Cerb UI form migration. These wire Cerb UI markup
 * (panels, toggles, buttons) into the existing peek/save plumbing; they own no styles of their own. As
 * legacy peeks/config forms convert, the small per-template helpers that used to live on `Devblocks.*`
 * land here under one namespace.
 *
 * CerbUI.Form.ConfirmDelete(scope, opts) — inline "are you sure?" delete confirm. The design-system
 *   replacement for the legacy `fieldset.delete` + `Devblocks.callbackPeekEditDeletePrompt/Cancel` pair:
 *   the peek's `.delete-prompt` button reveals a `cerb-ui-panel--alert` anchor (rendered by the shared
 *   `internal/peek/delete_confirm.tpl`) and hides the form's button row; Cancel reverses. The destructive
 *   action keeps its own binding — for peeks that's `button.delete` → `Devblocks.callbackPeekEditSave({mode:'delete'})`
 *   — unless you pass `onConfirm`.
 *
 *   CerbUI.Form.ConfirmDelete($popup[0], {
 *     trigger: '.delete-prompt',           // opens the confirm
 *     panel:   '[data-cerb-delete-confirm]',
 *     buttons: '.buttons',                 // hidden while the confirm is shown
 *     cancel:  '.delete-cancel',
 *     confirm: '.delete',                  // only wired when onConfirm is given
 *     onConfirm: null,                     // optional; omit to leave the confirm button's existing handler
 *   });
 */
CerbUI.Form = CerbUI.Form || {};

CerbUI.Form.ConfirmDelete = function(scope, opts = {}) {
	if(!window.jQuery || !scope)
		return;

	const o = Object.assign({
		trigger:   '.delete-prompt',
		panel:     '[data-cerb-delete-confirm]',
		buttons:   '.buttons',
		cancel:    '.delete-cancel',
		confirm:   '.delete',
		onConfirm: null,
	}, opts);

	const $root = jQuery(scope);

	// Reveal the alert panel, hide the form's button row.
	$root.find(o.trigger).on('click', function(e) {
		e.stopPropagation();
		$root.find(o.panel).fadeIn();
		$root.find(o.buttons).fadeOut();
	});

	// Dismiss: back to the button row.
	$root.find(o.cancel).on('click', function(e) {
		e.stopPropagation();
		$root.find(o.panel).fadeOut();
		$root.find(o.buttons).fadeIn();
	});

	// The confirm button keeps its own handler unless a callback is supplied.
	if(typeof o.onConfirm === 'function') {
		$root.find(o.confirm).on('click', function(e) {
			e.stopPropagation();
			o.onConfirm(e);
		});
	}
};
