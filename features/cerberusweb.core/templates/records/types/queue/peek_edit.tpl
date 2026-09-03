{$peek_context = CerberusContexts::CONTEXT_QUEUE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="invoke">
	<input type="hidden" name="module" value="queue">
	<input type="hidden" name="action" value="savePeekJson">
	<input type="hidden" name="view_id" value="{$view_id}">
	{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
	<input type="hidden" name="do_delete" value="0">
	<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-form">
			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" placeholder="(example.queue.name)" autofocus="autofocus" spellcheck="false">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.queue.consumer'|devblocks_translate|capitalize}</label>
					{if $model}
						<input type="hidden" name="extension_id" value="{$model->extension_id}">
						<div class="cerb-u-text-muted">{if $queue_extension}{$queue_extension->manifest->name}{else}{$model->extension_id}{/if}</div>
					{else}
						<select name="extension_id" data-cerb-queue-selectmenu>
							<option value="">({'common.choose'|devblocks_translate|lower})</option>
							{if !empty($queue_extensions)}
								{foreach from=$queue_extensions item=queue_ext}
									<option value="{$queue_ext->id}">{$queue_ext->name}</option>
								{/foreach}
							{/if}
						</select>
					{/if}
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Retries</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
					<input type="number" name="retry_max" value="{$model->retry_max|default:0}" min="0" max="16" style="width:5em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">max attempts (0 = never retry) over a window of</span>
					<input type="number" name="retry_window_secs" value="{$model->retry_window_secs|default:86400}" min="0" style="width:7em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">seconds</span>
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Claims</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
					<span class="cerb-u-text-muted">reclaim in-flight messages after</span>
					<input type="number" name="claim_window_secs" value="{$model->claim_window_secs|default:3600}" min="0" style="width:7em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">seconds without progress (0 = never)</span>
				</div>
			</div>

			{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
			{/if}
		</div>
	</div>

	<div class="queue-consumer-params">
		{if $queue_extension}
			{$queue_extension->renderConfig($model)}
		{/if}
	</div>

	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

	{if !empty($model->id)}
		{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="queue and its messages"}
	{/if}

	<div class="buttons" style="margin-top:10px;">
		{if $model->id}
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
			{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
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
			$popup.dialog('option','title',"{'common.queue'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
			$popup.css('overflow', 'inherit');

			let $extension = $popup.find('select[name=extension_id]');
			let $params = $popup.find('.queue-consumer-params');

			$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
			$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
			$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
			if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

			if(window.CerbUI && CerbUI.SelectMenu)
				$popup.find('select[data-cerb-queue-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

			{if !$model->id}
			$extension.on('change', function(e) {
				e.stopPropagation();
				let extension_id = $extension.val();

				if('' === extension_id) {
					$params.html('');
					return;
				}

				let $spinner = Devblocks.getSpinner();
				$params.html('').append($spinner);

				let formData = new FormData();
				formData.set('c', 'profiles');
				formData.set('a', 'invoke');
				formData.set('module', 'queue');
				formData.set('action', 'getExtensionConfig');
				formData.set('extension_id', extension_id);

				genericAjaxPost(formData, $params);
			});
			{/if}
		});
	});
</script>
