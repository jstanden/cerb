{$peek_context = CerberusContexts::CONTEXT_ADDRESS}
{$peek_context_id = $address->id}
{$form_id = "formAddressPeek{uniqid()}"}
<form action="#" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="address">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$address->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $address->id}
	{$contact = DAO_Contact::get($address->contact_id)}
	{$org = DAO_ContactOrg::get($address->contact_org_id)}
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.email'|devblocks_translate|capitalize}</label>
			{if !$address->id}
				{if !empty($email)}
					<input type="hidden" name="email" value="{$email}">
					<div class="cerb-u-text-muted">{$email}</div>
				{else}
					<input type="text" name="email" value="{$email}" class="required email" autocomplete="off" spellcheck="false" autofocus>
				{/if}
			{else}
				<div class="cerb-u-text-muted">{$address->email}</div>
			{/if}
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.organization'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="orgChooser_{$form_id}">
					{if $org}
						<li data-context-id="{$org->id}" data-label="{$org->name}" data-image="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}"></li>
					{/if}
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.contact'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="contactChooser_{$form_id}">
					{if $contact}
						<li data-context-id="{$contact->id}" data-label="{$contact->getName()}" data-image="{devblocks_url}c=avatars&context=contact&context_id={$contact->id}{/devblocks_url}?v={$contact->updated_at}"></li>
					{/if}
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.mail.filtering'|devblocks_translate|mb_ucfirst}</div>
	</div>
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input type="checkbox" name="is_banned" id="isBanned_{$form_id}" value="1" {if $address->is_banned}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="isBanned_{$form_id}">Reject incoming mail from this address <span class="cerb-u-text-muted">({'address.is_banned'|devblocks_translate|lower})</span></label>
				</div>
			</div>
			<div class="cerb-ui-form--field">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
					<label class="cerb-ui-toggle"><input type="checkbox" name="is_defunct" id="isDefunct_{$form_id}" value="1" {if $address->is_defunct}checked="checked"{/if}><span class="cerb-ui-toggle--slider"></span></label>
					<label for="isDefunct_{$form_id}">Reject outgoing mail to this address <span class="cerb-u-text-muted">({'address.is_defunct'|devblocks_translate|lower})</span></label>
				</div>
			</div>
		</div>
	</div>
</div>

{if $active_worker->is_superuser}
{$addr_type = ''}{if $address->mail_transport_id}{$addr_type = 'transport'}{elseif $address->worker_id}{$addr_type = 'worker'}{/if}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
			<div>
				<input type="hidden" name="type" id="addrType_{$form_id}" value="{$addr_type}">
				<div class="cerb-ui-switcher" id="addrTypeSwitcher_{$form_id}" data-cerb-input="addrType_{$form_id}">
					<button type="button" data-value="transport"{if $addr_type == 'transport'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-send"></span> We send email from this address</button>
					<button type="button" data-value="worker"{if $addr_type == 'worker'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-user"></span> A worker's personal address</button>
					<button type="button" data-value=""{if $addr_type == ''} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> None of the above</button>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field" data-cerb-type-transport{if $addr_type != 'transport'} style="display:none;"{/if}>
			<label class="cerb-ui-form--label">{'common.email_transport'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="transportChooser_{$form_id}">
				{$mail_transport = DAO_MailTransport::get($address->mail_transport_id)}
				{if $mail_transport}
					<li data-context-id="{$mail_transport->id}" data-label="{$mail_transport->name}"></li>
				{/if}
			</div>
		</div>

		<div class="cerb-ui-form--field" data-cerb-type-worker{if $addr_type != 'worker'} style="display:none;"{/if}>
			<label class="cerb-ui-form--label">{'common.worker'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="addrWorkerChooser_{$form_id}">
				{$worker = DAO_Worker::get($address->worker_id)}
				{if $worker}
					<li data-context-id="{$worker->id}" data-label="{$worker->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}"></li>
				{/if}
			</div>
		</div>
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$address->id}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
{if (!$address->id && $active_worker->hasPriv("contexts.{$peek_context}.create"))
	|| ($address->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
{else}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
		<div class="cerb-ui-header"><div class="cerb-ui-callout"><span class="cerb-icons cerb-icon-ban cerb-ui-callout--icon"></span><div class="cerb-ui-header--subtitle">{'error.core.no_acl.edit'|devblocks_translate}</div></div></div>
	</div>
{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#{$form_id}');

	Devblocks.formDisableSubmit($popup);

	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title', "{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.email_address'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		let contactChooser = null;

		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#orgChooser_{$form_id}')[0], {
				context: 'org',
				name: 'org_id',
				emptyIcon: 'building-office',
				create: 'if-null',
				searchPlaceholder: "{'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
				onSelect: function(item) {
					// When the org changes, scope the contact chooser to that org
					if(contactChooser && item && item.id) contactChooser.setQuery('org.id:' + item.id);
				}
			});

			contactChooser = new CerbUI.RecordChooser($popup.find('#contactChooser_{$form_id}')[0], {
				context: 'contact',
				name: 'contact_id',
				emptyIcon: 'user',
				create: 'if-null',
				{if $org}query: 'org.id:{$org->id}',{/if}
				searchPlaceholder: "{'common.contact'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			{if $active_worker->is_superuser}
			new CerbUI.RecordChooser($popup.find('#transportChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_MAIL_TRANSPORT}",
				name: 'mail_transport_id',
				emptyIcon: 'mail',
				searchPlaceholder: "{'common.email_transport'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#addrWorkerChooser_{$form_id}')[0], {
				context: 'worker',
				name: 'worker_id',
				emptyIcon: 'user',
				query: 'isDisabled:n',
				searchPlaceholder: "{'common.worker'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
			{/if}
		}

		// Address-type switcher → reveal the matching sub-chooser
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				let isType = (this.id === 'addrTypeSwitcher_{$form_id}');
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) {
						if(input) input.value = value;
						if(isType) {
							$popup.find('[data-cerb-type-transport]').toggle(value === 'transport');
							$popup.find('[data-cerb-type-worker]').toggle(value === 'worker');
						}
					}
				});
			});
		}
	});
});
</script>
