{$peek_context = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="webapi_credentials">
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
			<div class="cerb-ui-form--help">(e.g. "Server monitoring", "Call center integration")</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.worker'|devblocks_translate|capitalize}</label>
			{if !empty($model)}
				{$owner = $model->getWorker()}
				{if $owner}
					<div>
						<a class="cerb-ui-pill cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}"><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"> {$owner->getName()}</a>
					</div>
				{/if}
			{else}
				<div class="cerb-ui-record-chooser" id="workerChooser{$form_id}" data-context="{CerberusContexts::CONTEXT_WORKER}" data-name="worker_id" data-query="isDisabled:n"></div>
			{/if}
		</div>

		{if !empty($model)}
		<div class="cerb-ui-chip">
			<div>
				<div class="cerb-ui-chip--label">{'dao.webapi_credentials.access_key'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-chip--value">{$model->access_key}</div>
			</div>
			<div>
				<div class="cerb-ui-chip--label">{'dao.webapi_credentials.secret_key'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-chip--value">{$model->secret_key}</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle" id="regenKeys{$form_id}">
					<input type="checkbox" name="regenerate_keys" value="1">
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label for="regenKeys{$form_id}">Generate new keys</label>
			</div>
		</div>
		{/if}

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Allowed Endpoints</label>
			<textarea rows="8" name="params[allowed_paths]" spellcheck="false">{if empty($model)}*{elseif is_array($model->params.allowed_paths)}{implode("\n", $model->params.allowed_paths)}{/if}</textarea>
			<div class="cerb-ui-form--help">(one per line; use * for wildcards; e.g. tasks/*)</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="Web API credentials"}
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

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'webapi.common.api_credentials'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Worker chooser (create only)
		let workerEl = document.getElementById('workerChooser{$form_id}');
		if(workerEl && window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser(workerEl, {
				context: workerEl.getAttribute('data-context'),
				name: workerEl.getAttribute('data-name'),
				emptyIcon: 'user',
				query: workerEl.getAttribute('data-query')
			});

		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
	});
});
</script>
