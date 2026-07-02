{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER_CLASS}
{$peek_context_id = $model->id}
{$frm_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$frm_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier_class">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.classifier'|devblocks_translate|capitalize}</label>
			{if $model && $model->id}
				{$classifier = $model->getClassifier()}
				{if $classifier}
				<div>
					<a class="cerb-ui-pill cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-context-id="{$classifier->id}"><span class="cerb-icons cerb-icon-sparkles"></span> {$classifier->name}</a>
				</div>
				{/if}
			{else}
			<div class="cerb-ui-record-chooser" id="classifierChooser_{$frm_id}">
				{if $model}
					{$classifier = $model->getClassifier()}
					{if $classifier}
						<li data-context-id="{$classifier->id}" data-label="{$classifier->name}"></li>
					{/if}
				{/if}
			</div>
			{/if}
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="classifier class and all of its training data"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$frm_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.classifier.classification'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Triggers
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		if(window.CerbUI && CerbUI.RecordChooser) {
			let $classifierEl = $popup.find('#classifierChooser_{$frm_id}');
			if($classifierEl.length)
				new CerbUI.RecordChooser($classifierEl[0], {
					context: '{CerberusContexts::CONTEXT_CLASSIFIER}',
					name: 'classifier_id',
					emptyIcon: 'sparkles',
					searchPlaceholder: 'Classifier'
				});
		}

	});
});
</script>
