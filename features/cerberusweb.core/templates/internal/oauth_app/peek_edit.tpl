{$peek_context = CerberusContexts::CONTEXT_OAUTH_APP}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="oauth_app">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$model->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.oauth_app.client_id'|devblocks_translate|capitalize}</label>
				<input type="text" name="client_id" value="{$model->client_id}" spellcheck="false">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.oauth_app.client_secret'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-key"></span>
					<input type="text" name="client_secret" value="{$model->client_secret}" spellcheck="false">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.oauth_app.callback_url'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-link"></span>
					<input type="text" name="callback_url" value="{$model->callback_url}" spellcheck="false">
				</label>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.website'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-globe"></span>
					<input type="text" name="url" value="{$model->url}" spellcheck="false">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Scopes</label>
			<textarea name="scopes_yaml" data-editor-lines="10" spellcheck="false">{$model->scopes_yaml}</textarea>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.oauth_app.access_token_ttl'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-clock"></span>
					<input type="text" name="access_token_ttl" value="{$model->access_token_ttl}" placeholder="(1 hour)">
				</label>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'dao.oauth_app.refresh_token_ttl'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-clock"></span>
					<input type="text" name="refresh_token_ttl" value="{$model->refresh_token_ttl}" placeholder="(1 month)">
				</label>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if $model->id}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="OAuth app"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'OAuth App'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Scopes editor — KataEditor (no autocompletion yet; no oauth-scope dialect). [TODO] migrate scopes_yaml → KATA.
		var scopesEl = $popup.find('textarea[name=scopes_yaml]')[0];
		if(scopesEl && window.CerbUI && CerbUI.KataEditor)
			new CerbUI.KataEditor(scopesEl);
	});
});
</script>
