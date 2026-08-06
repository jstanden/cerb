{$peek_context = CerberusContexts::CONTEXT_CUSTOM_FIELD}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="custom_field">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="custom field"}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.uri'|devblocks_translate|capitalize}</label>
				<input type="text" name="uri" value="{$model->uri}">
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.record'|devblocks_translate|capitalize}</label>
				{if $model->id}
					<input type="hidden" name="context" value="{$model->context}">
					<div class="cerb-u-text-muted">{$context_mft = $context_mfts.{$model->context}}{if $context_mft}{$context_mft->name}{else}{$model->context}{/if}</div>
				{else}
					<select name="context" data-cerb-cfield-context-selectmenu>
						<option value="">({'common.choose'|devblocks_translate|lower})</option>
						{foreach from=$context_mfts item=ctx}
						<option value="{$ctx->id}" data-cerb-ui-icon="{$ctx->params.icon|default:'collection'}" {if $ctx->id == $model->context}selected="selected"{/if}>{$ctx->name}</option>
						{/foreach}
					</select>
				{/if}
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.fieldset'|devblocks_translate|capitalize} <span class="cerb-ui-form--hint">(optional)</span></label>
				<div class="cerb-ui-record-chooser" id="fieldsetChooser_{$form_id}">
					{if $model}
						{$custom_fieldset = $model->getFieldset()}
						{if $custom_fieldset}
							<li data-context-id="{$custom_fieldset->id}" data-label="{$custom_fieldset->name}"></li>
						{/if}
					{/if}
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.order'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-sort-asc" title="0...100, ascending; same order sorts alphabetically"></span></label>
				<input type="number" name="pos" min="0" max="100" value="{$model->pos|default:50}" style="width:5em;">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
				{if $model->id}
					<input type="hidden" name="type" value="{$model->type}">
					<div class="cerb-u-text-muted">{$type = $types.{$model->type}}{if $type}{$type}{else}{$model->type}{/if}</div>
				{else}
					<select name="type" data-cerb-cfield-type>
						<option value="">({'common.choose'|devblocks_translate|lower})</option>
						{foreach from=$types item=label key=key}
						<option value="{$key}" data-cerb-ui-icon="{$type_icons[$key]|default:'tag'}" {if $key == $model->type}selected="selected"{/if}>{$label}</option>
						{/foreach}
					</select>
				{/if}
			</div>
		</div>

		<div class="params">
			{$model->renderConfig()}
		</div>
	</div>
</div>

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="custom field"}
{/if}

<div class="status"></div>

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
		$popup.dialog('option','title',"{'Custom Field'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-cfield-context-selectmenu], select[data-cerb-cfield-type]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		// The type-specific params (currency / format / "to record type" pickers) are ajax-injected into
		// div.params, so enhance their selects from here — on the initial render and after each type change.
		let enhanceParamsSelects = function() {
			if(!(window.CerbUI && CerbUI.SelectMenu)) return;
			$popup.find('div.params select').each(function() {
				if(!CerbUI.SelectMenu.from(this)) new CerbUI.SelectMenu(this, { filter: true });
			});
		};
		enhanceParamsSelects();

		let fieldsetChooser = null;
		if(window.CerbUI && CerbUI.RecordChooser) {
			fieldsetChooser = new CerbUI.RecordChooser($popup.find('#fieldsetChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_CUSTOM_FIELDSET}",
				name: 'custom_fieldset_id',
				emptyIcon: 'collection',
				query: 'context:{$model->context}',
				searchPlaceholder: "{'common.fieldset'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
		}

		// When the record type changes, re-scope (and clear) the fieldset chooser
		$popup.find('select[name=context]').on('change', function(e) {
			if(fieldsetChooser) {
				fieldsetChooser.setQuery('context:' + $(this).val());
				if(typeof fieldsetChooser.setValue === 'function') fieldsetChooser.setValue([]);
			}
		});

		// When the type changes, draw new params
		$popup.find('select[name=type]').on('change', function(e) {
			let $params = $popup.find('div.params');
			genericAjaxGet($params, 'c=profiles&a=invoke&module=custom_field&action=getFieldParams&type=' + $(this).val(), enhanceParamsSelects);
		});
	});
});
</script>
