{$peek_context = 'cerb.contexts.task.project'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" class="cerb-ui-form">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="task_project">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-form--section">
	<div class="cerb-ui-form--section-body">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}: <span class="cerb-ui-form--required">*</span></label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field cerb-u-flex-2">
				<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}:</label>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
			</div>

			<div class="cerb-ui-form--field cerb-u-flex-1">
				<label class="cerb-ui-form--label">Archive:</label>
				<label class="cerb-ui-toggle">
					<input type="checkbox" name="is_closed" value="1" {if $model->is_closed}checked="checked"{/if}>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
			</div>
		</div>
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	</table>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="task project"}
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-trash cerb-u-anim-shake-hover"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'Task Project'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Inline delete confirm (reveals the cerb-ui-panel--alert, hides the button row); the actual
		// delete stays on button.delete above.
		if(window.CerbUI && CerbUI.Form)
			CerbUI.Form.ConfirmDelete($popup[0]);

		// Archive toggle
		let toggleEl = $popup.find('label.cerb-ui-toggle').get(0);
		if(toggleEl && window.CerbUI && CerbUI.Toggle)
			new CerbUI.Toggle(toggleEl);
	});
});
</script>
