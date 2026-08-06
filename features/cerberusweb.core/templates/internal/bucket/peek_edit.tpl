{$peek_context = CerberusContexts::CONTEXT_BUCKET}
{$peek_context_id = $bucket->id}
{$form_id = "frmBucketPeek{uniqid()}"}

{$is_mail_configured = $bucket->reply_address_id || $bucket->reply_personal || $bucket->reply_signature_id || $bucket->reply_signing_key_id || $bucket->reply_html_template_id}

{$replyto = DAO_Address::get($bucket->reply_address_id)}
{$signature = DAO_EmailSignature::get($bucket->reply_signature_id)}
{$signing_key = DAO_GpgPrivateKey::get($bucket->reply_signing_key_id)}
{$html_template = DAO_MailHtmlTemplate::get($bucket->reply_html_template_id)}
{$group = $groups.{$bucket->group_id}}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="bucket">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($bucket) && !empty($bucket->id)}<input type="hidden" name="id" value="{$bucket->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$bucket->name}" maxlength="64" autofocus="true">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.group'|devblocks_translate|capitalize}</label>
			{if !$bucket->id}
				<div class="cerb-ui-record-chooser" id="groupChooser_{$form_id}">
					{if $group}
						<li data-context-id="{$group->id}" data-label="{$group->name}" data-image="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}"></li>
					{/if}
				</div>
			{else}
				<input type="hidden" name="group_id" value="{$bucket->group_id}">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					{if $group}
						<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}">
						<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}">{$group->name}</a>
					{/if}
				</div>
			{/if}
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" name="enable_mail" id="enableMail_{$form_id}" value="1" {if $is_mail_configured}checked="checked"{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="enableMail_{$form_id}" class="cerb-ui-header--title-sm">Bucket-level mail settings <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
		</div>
	</div>

	<div class="cerb-ui-form" data-cerb-mail-settings {if !$is_mail_configured}style="display:none;"{/if}>
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.send.from'|devblocks_translate}</label>
			<div class="cerb-ui-record-chooser" id="replyAddressChooser_{$form_id}">
				{if $replyto}
					<li data-context-id="{$replyto->id}" data-label="{$replyto->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$replyto->id}{/devblocks_url}?v={$replyto->updated_at}"></li>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.send.as'|devblocks_translate}</label>
			{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="reply_personal" value=$bucket->reply_personal context="worker" placeholder="e.g. Customer Support" lines=2}
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.signature'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="signatureChooser_{$form_id}">
				{if $signature}
					<li data-context-id="{$signature->id}" data-label="{$signature->name}"></li>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.encrypt.signing.key'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="signingKeyChooser_{$form_id}">
				{if $signing_key}
					<li data-context-id="{$signing_key->id}" data-label="{$signing_key->name}"></li>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">HTML template</label>
			<div class="cerb-ui-record-chooser" id="htmlTemplateChooser_{$form_id}">
				{if $html_template}
					<li data-context-id="{$html_template->id}" data-label="{$html_template->name}"></li>
				{/if}
			</div>
		</div>
	</div>
</div>

{if !empty($custom_fields)}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.custom_fields'|devblocks_translate|capitalize}</div>
	</div>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$bucket->id}

{if !empty($bucket->id) && !$bucket->is_default}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert" style="display:none;" data-cerb-delete-confirm>
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-exclamation-mark cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">{'common.delete'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-header--subtitle">Permanently delete this bucket and move its tickets to:</div>
				<div class="cerb-u-mt-2">
					<select name="delete_moveto">
						{foreach from=$buckets item=move_bucket key=move_bucket_id}
						{if $move_bucket_id == $bucket->id}
						{elseif $bucket->group_id == $move_bucket->group_id}
						<option value="{$move_bucket_id}">{$move_bucket->name}</option>
						{/if}
						{/foreach}
					</select>
				</div>
			</div>
		</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-cancel"><span class="cerb-icons cerb-icon-ban"></span> {'common.no'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-ui-button delete"><span class="cerb-icons cerb-icon-trash"></span> {'common.yes'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</div>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $bucket->id}<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($bucket->id) && !$bucket->is_default && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title', '{'common.edit'|devblocks_translate|capitalize}: {'common.bucket'|devblocks_translate|capitalize|escape:'javascript' nofilter}');
		$popup.css('overflow', 'inherit');

		// Buttons

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Bucket-level mail settings toggle

		$popup.find('input[name=enable_mail]').on('click', function(e) {
			e.stopPropagation();
			$popup.find('[data-cerb-mail-settings]').toggle($(this).is(':checked'));
		});

		// Record choosers

		if(window.CerbUI && CerbUI.RecordChooser) {
			{if !$bucket->id}
			new CerbUI.RecordChooser($popup.find('#groupChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_GROUP}',
				name: 'group_id',
				emptyIcon: 'users',
				searchPlaceholder: "{'common.group'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
			{/if}

			new CerbUI.RecordChooser($popup.find('#replyAddressChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_ADDRESS}',
				name: 'reply_address_id',
				emptyIcon: 'mail',
				query: 'mailTransport.id:>0 isBanned:n isDefunct:n',
				searchPlaceholder: "{'common.send.from'|devblocks_translate|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#signatureChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}',
				name: 'reply_signature_id',
				emptyIcon: 'signature',
				searchPlaceholder: "{'common.signature'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#signingKeyChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_GPG_PRIVATE_KEY}',
				name: 'reply_signing_key_id',
				emptyIcon: 'key',
				searchPlaceholder: "{'common.encrypt.signing.key'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#htmlTemplateChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}',
				name: 'reply_html_template_id',
				emptyIcon: 'file',
				query: 'mailTransport.id:>0',
				searchPlaceholder: 'HTML template'
			});
		}

		// Abstract peeks (chips link to record cards)

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
	});
});
</script>
