{$uniq_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frm{$uniq_id}" name="frm{$uniq_id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="records">
<input type="hidden" name="action" value="renderMergeMappingPopup">
<input type="hidden" name="context" value="{$context_ext->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Select {$aliases.plural|lower} to merge:</b><br>
<div class="cerb-ui-record-chooser cerb-merge-chooser">
{if $dicts}
{foreach from=$dicts item=dict}
<li data-context="{$dict->_context}" data-context-id="{$dict->id}" data-label="{$dict->_label}"{if $context_ext->hasOption('avatars')} data-image="{devblocks_url}c=avatars&context={$context_ext->id}&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at}"{/if}></li>
{/foreach}
{/if}
</div>
<br>

{if $active_worker->hasPriv("contexts.{$context_ext->id}.merge")}
	<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.continue'|devblocks_translate|capitalize}</button>
{/if}
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frm{$uniq_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$(this).dialog('option','title', "Merge {$aliases.plural|capitalize}");

		// Chooser
		if(window.CerbUI && CerbUI.RecordChooser)
			$popup.find('.cerb-merge-chooser').each(function() {
				new CerbUI.RecordChooser(this, { context: '{$context_ext->id}', name: 'ids', multiple: true });
			});

		$frm.find('BUTTON.submit').click(function(e) {
			e.stopPropagation();
			// Replace the current form
			genericAjaxPost($frm, 'popuppeek', '');
		});
	});
});
</script>