{$peek_context = 'cerb.contexts.agent.model.router'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" class="cerb-ui-form">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="agent_model_router">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
	<input type="text" name="name" value="{$model->name}" placeholder="default" autocomplete="off" spellcheck="false" autofocus="autofocus">
	<div class="cerb-ui-form--hint">The router's URI handle &mdash; <code>cerb:agent_model_router:&lt;name&gt;</code>. Letters, numbers, dots, dashes, underscores.</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.label'|devblocks_translate|capitalize}</label>
	<input type="text" name="label" value="{$model->label}" placeholder="Default" autocomplete="off" spellcheck="false">
	<div class="cerb-ui-form--hint">The friendly name shown in pickers. Blank uses the name.</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
	<input type="text" name="description" value="{$model->description}" autocomplete="off">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.models'|devblocks_translate|capitalize}</label>
	{* A new router opens prefilled with the enabled models (assigned only when there's no record yet), so the
	   list is never typed by hand -- reorder and prune in the editor instead. *}
	<textarea name="models_kata" rows="10" spellcheck="false" style="width:100%;">{if $model->models_kata}{$model->models_kata}{else}{$models_kata_default}{/if}</textarea>
	<div class="cerb-ui-form--hint">
		One agent model name per entry, in preferred order &mdash; <code>Alt+&uarr;/&darr;</code> to reorder,
		<code>Alt+D</code> to delete a line. Use <code>&lt;name&gt;/&lt;alias&gt;:</code> to offer the same model
		more than once with different settings. A disabled model record is skipped everywhere but keeps its place here.
	</div>
</div>

<div class="cerb-ui-form--field">
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle">
			<input type="checkbox" id="router_default_{$form_id}" name="is_default" value="1" {if $model->is_default}checked="checked"{/if}>
			<span class="cerb-ui-toggle--slider"></span>
		</label>
		<label for="router_default_{$form_id}">{'common.default'|devblocks_translate|capitalize}</label>
	</div>
	<div class="cerb-ui-form--hint">The router used when nothing names one. Turning this on clears it on every other router.</div>
</div>

<div class="cerb-ui-form--field">
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle">
			<input type="checkbox" id="router_disabled_{$form_id}" name="is_disabled" value="1" {if $model->is_disabled}checked="checked"{/if}>
			<span class="cerb-ui-toggle--slider"></span>
		</label>
		<label for="router_disabled_{$form_id}">{'common.disabled'|devblocks_translate|capitalize}</label>
	</div>
</div>

{if !empty($custom_fields)}
<table cellspacing="0" cellpadding="2" border="0" width="98%">
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
</table>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>

	<div>
		Are you sure you want to permanently delete this agent model router?
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="delete-cancel">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	{if $model->id}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		<button type="button" class="save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="delete-prompt"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<button type="button" class="save"><span class="cerb-icons cerb-icon-circle-plus"></span> {'common.create'|devblocks_translate|capitalize}</button>
	{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);
	const modelsAutocomplete = {$models_autocomplete_json nofilter};

	Devblocks.formDisableSubmit($frm);

	if(window.CerbUI && CerbUI.Toggle)
		$frm.find('.cerb-ui-toggle').each(function() { new CerbUI.Toggle(this); });

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Agent Model Router'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.find('[autofocus]:first').focus();
		$popup.css('overflow', 'inherit');

		// The models editor autocompletes from the live `agent_model` records (built server-side per popup) via
		// the same helper `llm.agent: model:` uses -- so what's offered here is exactly what that command accepts,
		// and a model added later shows up with no change to this form.
		if(window.CerbUI && CerbUI.KataEditor) {
			const modelsEl = $frm.find('textarea[name=models_kata]')[0];

			if(modelsEl) {
				new CerbUI.KataEditor(modelsEl, {
					minLines: 8,
					maxLines: 24,
					onAutocomplete: CerbUI.KataEditor.kataFieldSource(modelsAutocomplete)
				});
			}
		}

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete-prompt').click(Devblocks.callbackPeekEditDeletePrompt);
		$popup.find('button.delete-cancel').click(Devblocks.callbackPeekEditDeleteCancel);
	});
});
</script>
