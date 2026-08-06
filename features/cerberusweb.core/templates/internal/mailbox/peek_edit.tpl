{$peek_context = 'cerberusweb.contexts.mailbox'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="mailbox">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $model->enabled && $model->num_fails}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Error!</div>
				<div class="cerb-ui-header--subtitle">This mailbox has failed to check mail for {$model->num_fails} consecutive attempt{if $model->num_fails > 1}s{/if}.</div>
			</div>
		</div>
	</div>
</div>
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.enabled'|devblocks_translate|capitalize}</label>
			<label class="cerb-ui-toggle">
				<input type="checkbox" name="enabled" value="1" {if $model->enabled || empty($model)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field cerb-u-flex-2">
				<label class="cerb-ui-form--label">Protocol</label>
				<select name="protocol" data-cerb-protocol>
					<option value="pop3-starttls" {if $model->protocol=='pop3-starttls'}selected{/if}>POP3 (STARTTLS)</option>
					<option value="pop3-ssl" {if $model->protocol=='pop3-ssl'}selected{/if}>POP3 (TLS/SSL)</option>
					<option value="pop3" {if $model->protocol=='pop3'}selected{/if}>POP3 (Unencrypted)</option>
					<option value="imap-starttls" {if $model->protocol=='imap-starttls'}selected{/if}>IMAP (STARTTLS)</option>
					<option value="imap-ssl" {if $model->protocol=='imap-ssl'}selected{/if}>IMAP (TLS/SSL)</option>
					<option value="imap" {if $model->protocol=='imap'}selected{/if}>IMAP (Unencrypted)</option>
				</select>
			</div>
			<div class="cerb-ui-form--field cerb-u-flex-1">
				<label class="cerb-ui-form--label">Port</label>
				<input type="text" name="port" value="{$model->port}">
				<div class="cerb-ui-form--help">(leave blank for default)</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.host'|devblocks_translate|capitalize}</label>
			<input type="text" name="host" value="{$model->host}">
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.user'|devblocks_translate|capitalize}</label>
				<input type="text" name="username" value="{$model->username}">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.password'|devblocks_translate|capitalize}</label>
				<input type="password" name="password" value="{$model->password}" autocomplete="off" spellcheck="false">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">XOAuth2 <span class="cerb-ui-form--hint">({'common.optional'|devblocks_translate|lower})</span></label>
			<div class="cerb-ui-record-chooser" id="accountChooser_{$form_id}">
				{if $model && $model->connected_account_id}
					{$account = DAO_ConnectedAccount::get($model->connected_account_id)}
					{if $account}
						<li data-context-id="{$account->id}" data-label="{$account->name}"></li>
					{/if}
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Timeout</label>
				<input type="text" name="timeout_secs" value="{$model->timeout_secs|default:30}">
				<div class="cerb-ui-form--help">seconds</div>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Max message size</label>
				<input type="text" name="max_msg_size_kb" value="{$model->max_msg_size_kb|default:25600}">
				<div class="cerb-ui-form--help">KB</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--subtitle">Messages in this mailbox are deleted once downloaded. If that isn't desirable (e.g. IMAP), create a disposable mailbox to use instead and have copies of your incoming mail sent to it.</div>
			</div>
		</div>
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="mailbox"}
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle tester"><span class="cerb-icons cerb-icon-gear"></span> {'common.test'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'Mailbox'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Protocol
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-protocol]').each(function() { new CerbUI.SelectMenu(this); });

		// XOAuth2 connected account
		if(window.CerbUI && CerbUI.RecordChooser)
			new CerbUI.RecordChooser($popup.find('#accountChooser_{$form_id}')[0], {
				context: '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}',
				name: 'connected_account_id',
				emptyIcon: 'key',
				query: 'service:(type:oauth2)',
				searchPlaceholder: 'XOAuth2'
			});
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Tester
		$popup.find('button.tester').click(function() {
			var $button = $(this);
			$button.hide();

			Devblocks.clearAlerts();
			Devblocks.createAlert('Testing mailbox... please wait.', null, 0);

			var formData = new FormData($frm[0]);
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'mailbox');
			formData.set('action', 'testMailboxJson');

			genericAjaxPost(formData,'','',function(json) {
				Devblocks.clearAlerts();

				if('object' != typeof json || false == json.status) {
					Devblocks.createAlertError(json.error);
				} else {
					Devblocks.createAlert('Connected to your mailbox successfully!', 'success', 5000);
				}

				$button.show();
			});
		});
	});
});
</script>
