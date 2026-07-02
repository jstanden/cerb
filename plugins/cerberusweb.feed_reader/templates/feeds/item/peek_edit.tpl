{$peek_context = CerberusContexts::CONTEXT_FEED_ITEM}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{if $model->is_closed}{$is_closed = 1}{else}{$is_closed = 0}{/if}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="feed_item">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
			<div>{$model->title}</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.url'|devblocks_translate|upper}</label>
			<div><a href="{$model->url}" target="_blank" rel="noopener noreferrer" class="cerb-u-underline-hover">{$model->url|truncate:64}</a></div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="is_closed" id="isClosed{$form_id}" value="{$is_closed}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="isClosed{$form_id}">
					<button type="button" data-value="0" {if !$is_closed}class="cerb-ui-switcher--active"{/if}>{'status.open'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="1" {if $is_closed}class="cerb-ui-switcher--active"{/if}>{'status.closed'|devblocks_translate|capitalize}</button>
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="feed item"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'feeds.item'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Status switcher (open/closed) bound to the hidden is_closed input
		let statusInput = document.getElementById('isClosed{$form_id}');
		let statusEl = $popup.find('[data-cerb-input="isClosed{$form_id}"]')[0];
		if(statusInput && statusEl && window.CerbUI && CerbUI.Switcher)
			new CerbUI.Switcher(statusEl, {
				value: statusInput.value,
				onSelect: function(value) { statusInput.value = value; }
			});
	});
});
</script>
