{$peek_context = CerberusContexts::CONTEXT_CLASSIFIER_ENTITY}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{$active_type = $model->type|default:'list'}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" class="cerb-form">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="classifier_entity">
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
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.description'|devblocks_translate|capitalize}</label>
				<input type="text" name="description" value="{$model->description}" placeholder="">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="type" id="entityType{$form_id}" value="{$active_type}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="entityType{$form_id}">
					<button type="button" data-value="list" {if $active_type == 'list'}class="cerb-ui-switcher--active"{/if}>List</button>
					<button type="button" data-value="regexp" {if $active_type == 'regexp'}class="cerb-ui-switcher--active"{/if}>Token Regexp</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field" data-cerb-entity-type-panel="list" {if $active_type != 'list'}style="display:none;"{/if}>
			<textarea name="params[list][labels]" rows="8">{if $model->type == 'list'}{$model->params.labels}{/if}</textarea>
			<div class="cerb-ui-form--help">
				<tt>&lt;label&gt;, &lt;alias&gt;</tt> &nbsp; e.g.:
				<pre style="margin:0px 0px 0px 20px;"><code>mobile, cell
mobile, cellphone
mobile, mobile
website, homepage
website, url
website, website</code></pre>
			</div>
		</div>

		<div class="cerb-ui-form--field" data-cerb-entity-type-panel="regexp" {if $active_type != 'regexp'}style="display:none;"{/if}>
			<textarea name="params[regexp][pattern]" rows="3">{if $model->type == 'regexp'}{$model->params.pattern}{/if}</textarea>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="classifier entity"}
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
		$popup.dialog('option','title',"{'common.classifier.entity'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Type switcher: reveal the matching params panel; both textareas stay in the DOM (the save handler
		// only reads the selected type's params).
		let typeInput = document.getElementById('entityType{$form_id}');
		let switcherEl = $popup.find('[data-cerb-input="entityType{$form_id}"]')[0];

		let applyType = function(value) {
			$popup.find('[data-cerb-entity-type-panel]').each(function() {
				$(this).toggle(this.getAttribute('data-cerb-entity-type-panel') === value);
			});
		};

		if(switcherEl && window.CerbUI && CerbUI.Switcher)
			new CerbUI.Switcher(switcherEl, {
				value: typeInput.value,
				onSelect: function(value) {
					typeInput.value = value;
					applyType(value);
				}
			});

		applyType(typeInput.value);

	});
});
</script>
