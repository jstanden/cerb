{$peek_context = 'cerberusweb.contexts.mailbox'}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="mailbox">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
	{if $model->enabled && $model->num_fails}
	<tr>
		<td colspan="2">
			<div class="error-box">
				<h1>
					<span class="glyphicons glyphicons-circle-exclamation-mark"></span>
					Error!
				</h1>
				<p>
					This mailbox has failed to check mail for {$model->num_fails} consecutive attempt{if $model->num_fails > 1}s{/if}.
				</p>
			</div>
		</td>
	</tr>
	{/if}
	<tr>
		<td width="0%" nowrap="nowrap"><b>{'common.enabled'|devblocks_translate|capitalize}:</b></td>
		<td width="100%">
			<input type="checkbox" name="enabled" value="1" {if $model->enabled || empty($model)}checked{/if}>
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Protocol:</b></td>
		<td width="100%">
			<select name="protocol">
				<option value="pop3-starttls" {if $model->protocol=='pop3-starttls'}selected{/if}>POP3 (STARTTLS)
				<option value="pop3-ssl" {if $model->protocol=='pop3-ssl'}selected{/if}>POP3 (TLS/SSL)
				<option value="pop3" {if $model->protocol=='pop3'}selected{/if}>POP3 (Unencrypted)
				<option value="imap-starttls" {if $model->protocol=='imap-starttls'}selected{/if}>IMAP (STARTTLS)
				<option value="imap-ssl" {if $model->protocol=='imap-ssl'}selected{/if}>IMAP (TLS/SSL)
				<option value="imap" {if $model->protocol=='imap'}selected{/if}>IMAP (Unencrypted)
			</select>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>{'common.host'|devblocks_translate|capitalize}:</b></td>
		<td width="100%">
			<input type="text" name="host" value="{$model->host}" size="45">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>{'common.user'|devblocks_translate|capitalize}:</b></td>
		<td width="100%">
			<input type="text" name="username" value="{$model->username}">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>{'common.password'|devblocks_translate|capitalize}:</b></td>
		<td width="100%">
			<input type="password" name="password" value="{$model->password}">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>XOAuth2:</b><br><small>({'common.optional'|devblocks_translate|lower})</small></td>
		<td width="100%">
			<button type="button" class="chooser-abstract" data-field-name="connected_account_id" data-context="{Context_ConnectedAccount::ID}" data-single="true" data-query="service:(type:oauth2)"><span class="glyphicons glyphicons-search"></span></button>

			<ul class="bubbles chooser-container">
				{if $model && $model->connected_account_id}
					{$account = DAO_ConnectedAccount::get($model->connected_account_id)}
					{if $account}
						<li><input type="hidden" name="connected_account_id" value="{$account->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{Context_ConnectedAccount::ID}" data-context-id="{$account->id}">{$account->name}</a></li>
					{/if}
				{/if}
			</ul>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Port:</b></td>
		<td width="100%">
			<input type="text" name="port" value="{$model->port}" size="5"> (leave blank for default)
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Timeout:</b></td>
		<td width="100%">
			<input type="text" name="timeout_secs" value="{$model->timeout_secs|default:30}" size="5"> seconds
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap"><b>Max Msg Size:</b></td>
		<td width="100%">
			<input type="text" name="max_msg_size_kb" value="{$model->max_msg_size_kb|default:25600}" size="6"> KB
		</td>
	</tr>
	<tr>
		<td colspan="2" style="padding-top:10px;">
			<b>Note:</b> Messages in this mailbox will be deleted once they are downloaded. If this is not desirable 
			behavior (e.g. IMAP), please create a disposible mailbox to use instead and have copies of your incoming 
			mail sent to it.
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this mailbox?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<button type="button" class="tester"><span class="glyphicons glyphicons-cogwheel"></span> {'common.test'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Mailbox'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('.chooser-abstract').cerbChooserTrigger();
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Close confirmation

		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode === 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Tester
		
		$popup.find('BUTTON.tester')
			.click(function() {
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
			})
		;
		
	});
});
</script>
