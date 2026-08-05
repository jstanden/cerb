{* "What changed" popup: a side-by-side CerbUI.DiffViewer of the Run tab's Input against its Output, so an author
   can read what the automation actually did instead of eyeballing two YAML docs. Both sides arrive already
   canonicalized by _canonicalizeState() — the panes are authored in different key orders, and comparing them as
   authored reports the same data at different positions as a rewrite. collapseUnchanged elides the long runs of
   identical lines behind a clickable tear; the gutter keeps printing model line numbers, so they jump across it. *}
{$uniqid = uniqid('stateDiff')}
<div id="{$uniqid}" data-cerb-dialog-title="Input &rarr; Output">
	{if $error}
		<div class="cerb-ui-panel cerb-ui-panel--alert">
			<div class="cerb-ui-panel--body">{$error}</div>
		</div>
	{else}
		<div data-cerb-state-diff></div>
	{/if}

	<div class="buttons" style="margin-top:10px;">
		<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-close><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.close'|devblocks_translate|capitalize}</button>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $div = $('#{$uniqid}');
	const $popup = genericAjaxPopupFind($div);

	$popup.find('[data-cerb-close]').on('click', function() { genericAjaxPopupClose($popup); });

	{if !$error}
	const host = $div.find('[data-cerb-state-diff]')[0];

	if(host && window.CerbUI && CerbUI.DiffViewer) {
		const viewer = new CerbUI.DiffViewer(host, {
			left: {$diff_before|json_encode nofilter},
			right: {$diff_after|json_encode nofilter},
			lines: 26,
			collapseUnchanged: true,
			dragKeys: true
		});

		// Hand the viewer to the opener (same seam the changeset diff popup uses) so it can wire what a key
		// dragged/clicked out of the Output side does — this popup has no idea what opened it. The dialog isn't
		// modal, so drag straight onto the editor behind it; move this out of the way first if it's covering it.
		$popup.triggerHandler($.Event('cerb-diff-viewer-ready', { viewer: viewer }));
	}
	{/if}
});
</script>
