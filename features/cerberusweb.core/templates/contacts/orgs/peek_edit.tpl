{$peek_context = CerberusContexts::CONTEXT_ORG}
{$peek_context_id = $org->id}
{$form_id = "peek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="org">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$org->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $org instanceof Model_ContactOrg}
	{$addy = $org->getEmail()}
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="org_name" value="{$org->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.aliases'|devblocks_translate|capitalize} <span class="cerb-ui-form--hint">(press Enter to add)</span></label>
			<div class="cerb-ui-tag-input" id="aliasesInput_{$form_id}" data-name="aliases">
				{foreach from=$aliases item=alias_val}
					<input type="text" name="aliases[]" maxlength="255" value="{$alias_val}">
				{/foreach}
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'contact_org.street'|devblocks_translate|capitalize}</label>
			<textarea name="street" style="height:50px;">{$org->street}</textarea>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'contact_org.city'|devblocks_translate|capitalize}</label>
				<input type="text" name="city" value="{$org->city}">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'contact_org.province'|devblocks_translate|capitalize}</label>
				<input type="text" name="province" value="{$org->province}">
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'contact_org.postal'|devblocks_translate|capitalize}</label>
				<input type="text" name="postal" value="{$org->postal}">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'contact_org.country'|devblocks_translate|capitalize}</label>
				<input type="text" name="country" value="{$org->country}">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.email'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="emailChooser_{$form_id}">
				{if $addy}
					<li data-context-id="{$addy->id}" data-label="{$addy->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}"></li>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.phone'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-phone-handset"></span>
					<input type="text" name="phone" value="{$org->phone}">
				</label>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.website'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-link"></span>
					<input type="text" name="website" value="{$org->website}" class="url">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.image'|devblocks_translate|capitalize}</label>
			<div>
				<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
					data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}" data-name="avatar_image"
					data-avatar="{$org->name}" data-avatar-seed="org:{$org->id}"
					data-avatar-image="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}"></span>
				<input type="hidden" name="avatar_image" value="">
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ORG context_id=$org->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

{if !empty($org->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="organization"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	{if (!$org->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($org->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	{if $active_worker->hasPriv("contexts.{$peek_context}.delete") && !empty($org->id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title', "{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Country autocomplete
		if(window.CerbUI && CerbUI.TextChooser)
			$popup.find('form input[name=country]').each(function() {
				new CerbUI.TextChooser(this, { icon: 'globe', source: 'c=profiles&a=invoke&module=org&action=autocompleteCountry' });
			});

		// Avatar
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Aliases tag input (posts aliases[]; persisted CRLF-delimited)
		if(window.CerbUI && CerbUI.TagInput) {
			let aliasesEl = $popup.find('#aliasesInput_{$form_id}')[0];
			if(aliasesEl)
				new CerbUI.TagInput(aliasesEl, { placeholder: 'Add an alias and press Enter…' });
		}

		// Email chooser
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#emailChooser_{$form_id}')[0], {
				context: 'address',
				name: 'email_id',
				emptyIcon: 'mail',
				query: 'mailTransport.id:0 isBanned:n isDefunct:no',
				create: 'if-null',
				searchPlaceholder: "{'common.email'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
		}

		$popup.find(':input:text:first').focus();
	});
});
</script>
