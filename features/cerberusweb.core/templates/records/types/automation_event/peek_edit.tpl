{$peek_context = CerberusContexts::CONTEXT_AUTOMATION_EVENT}
{$peek_context_id = $model->id}
{$form_id = uniqid('form')}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="automation_event">
	<input type="hidden" name="action" value="savePeekJson">
	<input type="hidden" name="view_id" value="{$view_id}">
	{if $model && $model->id}
		<input type="hidden" name="id" value="{$model->id}">
		<input type="hidden" name="name" value="{$model->name}">
	{/if}
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div>
				<div class="cerb-ui-header--title">{$model->name}</div>
				{if $model->description}<div class="cerb-ui-header--subtitle">{$model->description}</div>{/if}
			</div>
		</div>

		{if !empty($custom_fields)}
		<div class="cerb-ui-form">
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		</div>
		{/if}
	</div>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

	<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-event-listeners>
		{include file="devblocks:cerberusweb.core::records/types/automation_event/listeners.tpl" event_id=$model->extension_id event_name=$model->name}
	</div>

	<div class="buttons" style="margin-top:10px;">
		{if $model->id}
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{else}
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
		{/if}
	</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		let $frm = $('#{$form_id}');
		let $popup = genericAjaxPopupFind($frm);

		Devblocks.formDisableSubmit($frm);

		$popup.one('popup_open', function() {
			$popup.dialog('option','title',"{'Automation Event'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
			$popup.css('overflow', 'inherit');

			$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
			$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		});
	});
</script>
