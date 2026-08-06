{$peek_context = CerberusContexts::CONTEXT_SAVED_SEARCH}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="saved_search">
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
				<label class="cerb-ui-form--label">{'common.tag'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-tag"></span>
					<input type="text" name="tag" value="{$model->tag}" placeholder="e.g. eu-sales (letters, numbers, dash)">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
				<div>{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}</div>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
				<select name="context" data-cerb-savedsearch-selectmenu>
					{foreach from=$contexts item=ctx key=k}
					<option value="{$k}" {if $model->context==$k}selected="selected"{/if}>{$ctx->name}</option>
					{/foreach}
				</select>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.query'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-searchquery">
				<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
				<div class="cerb-ui-searchquery--field">
					<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
					<textarea name="query" class="cerb-ui-searchquery--input" rows="1">{$model->query}</textarea>
					<span class="cerb-ui-searchquery--caret-anchor"></span>
				</div>
				<div class="cerb-ui-searchquery--right">
					<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
				</div>
			</div>
		</div>
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="saved search"}
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

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Saved Search'|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		var $context = $popup.find('select[name=context]');
		var sqEl = $popup.find('.cerb-ui-searchquery')[0];

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Search-query editor (context follows the record-type dropdown)
		var savedSearchSq = null;
		if(sqEl && window.CerbUI && CerbUI.SearchQuery) {
			savedSearchSq = new CerbUI.SearchQuery(sqEl, {
				onAutocomplete: CerbUI.SearchQuery.queryFieldSource($context.val()),
				context: $context.val(),
			});
			var acBtn = sqEl.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => savedSearchSq.openAutocomplete());
		}

		$context.change(function(e) {
			if(savedSearchSq) savedSearchSq.setContext($(this).val());
		});

		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-savedsearch-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
	});
});
</script>
