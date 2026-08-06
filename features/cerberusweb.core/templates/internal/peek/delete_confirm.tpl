{*
	Shared inline delete-confirm for record peeks — a cerb-ui-panel--alert revealed by the peek's
	`.delete-prompt` button and wired with `CerbUI.Form.ConfirmDelete($popup[0])`. The peek's `.delete`
	button still triggers the actual delete (`Devblocks.callbackPeekEditSave({mode:'delete'})`).

	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="event listener"}

	Optional `detail` adds a second subtitle line for extra consequences (emitted nofilter — pass safe HTML/text).
*}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert" style="display:none;" data-cerb-delete-confirm>
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-exclamation-mark cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">{'common.delete'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-header--subtitle">Are you sure you want to permanently delete this {$noun|default:'record'}?</div>
				{if !empty($detail)}<div class="cerb-ui-header--subtitle">{$detail nofilter}</div>{/if}
			</div>
		</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-cancel"><span class="cerb-icons cerb-icon-ban"></span> {'common.no'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-ui-button delete"><span class="cerb-icons cerb-icon-trash"></span> {'common.yes'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</div>
