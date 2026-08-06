{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_WORKLIST}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="workspace_list">
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
				<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
				{if !$model->id}
				<select name="context" data-cerb-worklist-selectmenu>
					<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
					{foreach from=$contexts item=context key=context_id}
					<option value="{$context_id}" {if $model->context == $context}selected="selected"{/if}>{$context}</option>
					{/foreach}
				</select>
				{else}
				<div class="cerb-u-text-muted">{$contexts.{$model->context}}</div>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'dashboard'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="tabChooser_{$form_id}">
				{$tab = $model->getWorkspaceTab()}
				{if $tab}
					<li data-context-id="{$tab->id}" data-label="{$tab->name}"></li>
				{/if}
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="workspace list"}
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
		$popup.dialog('option','title',"{'common.workspace.worklist'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-worklist-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#tabChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_WORKSPACE_TAB}",
				name: 'workspace_tab_id',
				emptyIcon: 'dashboard',
				query: 'type:"core.workspace.tab.worklists"',
				searchPlaceholder: "{'dashboard'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
		}
	});
});
</script>
