{$peek_context = CerberusContexts::CONTEXT_GROUP}
{$peek_context_id = $group->id}
{$form_id = "formGroupsPeek{uniqid()}"}
<div id="routingAgentMount{$form_id}">
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="group">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($group) && !empty($group->id)}<input type="hidden" name="id" value="{$group->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="{$form_id}Tabs">
	<ul>
		<li><a href="#{$form_id}Profile">{'common.profile'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Inbox">{'common.mail.incoming'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Mail">{'common.mail.outgoing'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Members">{'common.members'|devblocks_translate|capitalize}</a></li>
	</ul>

	{* ─────────────── Profile ─────────────── *}
	<div id="{$form_id}Profile">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{* Name *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$group->name}" autocomplete="off" autofocus="autofocus">
				</div>

				{* Type + Image *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
						<div>
							<input type="hidden" name="is_private" id="isPrivate_{$form_id}" value="{$group->is_private}">
							<div class="cerb-ui-switcher" data-cerb-input="isPrivate_{$form_id}">
								<button type="button" data-value="0"{if !$group->is_private} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-eye-open"></span> {'common.public'|devblocks_translate|capitalize}</button>
								<button type="button" data-value="1"{if $group->is_private} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-lock"></span> {'common.private'|devblocks_translate|capitalize}</button>
							</div>
							<div class="cerb-ui-form--help">Public group content is visible to non-members; private content is hidden from non-members.</div>
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.image'|devblocks_translate|capitalize}</label>
						<div>
							<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
								data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}" data-name="avatar_image"
								data-avatar="{$group->name}" data-avatar-seed="group:{$group->id}"
								data-avatar-image="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}"></span>
							<input type="hidden" name="avatar_image" value="">
						</div>
					</div>
				</div>

				{* Custom fields (cerb-ui renderer) *}
				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
				{/if}
			</div>
		</div>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_GROUP context_id=$group->id}
	</div>

	{* ─────────────── Mail: Incoming (routing) — CerbUI.KataEditor ─────────────── *}
	<div id="{$form_id}Inbox">
		<fieldset data-cerb-bucket-routing-toolbar class="peek">
			<legend>When a new ticket arrives in the {if $group && $group->id}{$group->name}{else}group{/if} inbox: (KATA)</legend>
			<ul class="cerb-ui-toolbar" data-cerb-bucket-routing-toolbar-items hidden>
				<li data-value="suggest" data-icon="autocomplete" title="{'common.autocomplete'|devblocks_translate|capitalize} (Ctrl/⌘+Space)"></li>
				{if $group->id}
					<li data-value="changesets" data-icon="history" title="{'common.change_history'|devblocks_translate|capitalize}"></li>
				{/if}
				<li></li>
				<li data-value="help" data-toggle data-key="help" data-icon="circle-question-mark" title="{'common.help'|devblocks_translate|capitalize}"></li>
				<li data-value="tester" data-toggle data-key="tester" data-icon="lab" title="{'common.test'|devblocks_translate|capitalize}"></li>
			</ul>

			<textarea name="routing_kata" data-editor-lines="20" spellcheck="false">{$group->routing_kata}</textarea>
		</fieldset>

		<fieldset data-cerb-fieldset-help class="peek black cerb-hidden">
			<legend style="font-size:140%;">{'common.help'|devblocks_translate|capitalize}</legend>

			{if $routing_placeholders}
				<h3 style="padding:0;margin:0 0 5px 0;">{'common.placeholders'|devblocks_translate|capitalize}</h3>
				<div>
					<div class="cerb-markdown-content">
						<table cellpadding="2" cellspacing="2" width="100%">
							<colgroup>
								<col style="width:1%;white-space:nowrap;">
								<col style="padding-left:10px;">
							</colgroup>
							<tbody>
							{foreach from=$routing_placeholders item=placeholder_notes key=placeholder_key}
								<tr>
									<td valign="top">
										<strong><code>{$placeholder_key}</code></strong>
									</td>
									<td>
										{$placeholder_notes|devblocks_markdown_to_html nofilter}
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			{/if}
		</fieldset>

		<fieldset data-cerb-routing-tester class="peek black cerb-hidden" style="margin:10px 0 0 0;">
			<legend style="font-size:140%;">{'common.test'|devblocks_translate|capitalize}</legend>

			<div>
				<div data-cerb-routing-tester-editor-placeholders>
					<div class="cerb-ui-editor-toolbar cerb-u-flex cerb-u-items-center cerb-u-gap-2">
						<span class="cerb-u-text-muted cerb-u-fs-n1">{'common.placeholders'|devblocks_translate|capitalize} (KATA)</span>
						<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-code-editor-toolbar-button--chooser" title="{'common.choose'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-search"></span></button>
						<button type="button" class="cerb-ui-button cerb-ui-button--transparent cerb-code-editor-toolbar-button--run" title="{'common.run'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-play"></span></button>
					</div>
					<textarea name="tester[placeholders]" data-editor-lines="6" spellcheck="false"></textarea>
				</div>

				<div data-cerb-routing-tester-results style="margin-top:10px;position:relative;"></div>
			</div>
		</fieldset>
	</div>

	{* ─────────────── Mail: Outgoing ─────────────── *}
	<div id="{$form_id}Mail">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">Default for all group buckets</div>
			</div>

			<div class="cerb-ui-form">
				{* Send from *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.send.from'|devblocks_translate|capitalize}</label>
					<div class="cerb-ui-record-chooser" id="replyAddressChooser_{$form_id}">
						{$replyto = DAO_Address::get($group->reply_address_id)}
						{if $replyto}
							<li data-context-id="{$replyto->id}" data-label="{$replyto->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$replyto->id}{/devblocks_url}?v={$replyto->updated_at}"></li>
						{/if}
					</div>
				</div>

				{* Send as *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.send.as'|devblocks_translate|capitalize}</label>
					{include file="devblocks:cerberusweb.core::internal/editors/template_field.tpl" name="reply_personal" value=$group->reply_personal context="worker" placeholder="e.g. Customer Support" lines=2 gutter=false}
				</div>

				{* Signature + Signing key *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.signature'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="replySignatureChooser_{$form_id}">
							{$signature = DAO_EmailSignature::get($group->reply_signature_id)}
							{if $signature}
								<li data-context-id="{$signature->id}" data-label="{$signature->name}"></li>
							{/if}
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.encrypt.signing.key'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="replySigningKeyChooser_{$form_id}">
							{$signing_key = DAO_GpgPrivateKey::get($group->reply_signing_key_id)}
							{if $signing_key}
								<li data-context-id="{$signing_key->id}" data-label="{$signing_key->name}"></li>
							{/if}
						</div>
					</div>
				</div>

				{* HTML template + Masks *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.html_mail_template'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="replyHtmlTemplateChooser_{$form_id}">
							{$html_template = DAO_MailHtmlTemplate::get($group->reply_html_template_id)}
							{if $html_template}
								<li data-context-id="{$html_template->id}" data-label="{$html_template->name}"></li>
							{/if}
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">Ticket masks</label>
						<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
							<label class="cerb-ui-toggle">
								<input type="checkbox" name="subject_has_mask" id="subjectHasMask_{$form_id}" value="1" {if $group->subject_has_mask}checked="checked"{/if}>
								<span class="cerb-ui-toggle--slider"></span>
							</label>
							<label for="subjectHasMask_{$form_id}">Include ticket masks in message subjects</label>
						</div>
					</div>
				</div>

				{* Subject prefix — revealed when masks are on *}
				<div class="cerb-ui-form--field" id="subjectPrefix_{$form_id}"{if !$group->subject_has_mask} style="display:none;"{/if}>
					<label class="cerb-ui-form--label">Subject prefix <span class="cerb-ui-form--hint">(optional, e.g. "billing", "tech-support")</span></label>
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						<span class="cerb-u-text-muted">Re: [</span>
						<input type="text" name="subject_prefix" placeholder="prefix" value="{$group->subject_prefix}" style="flex:0 1 14em;min-width:0;">
						<span class="cerb-u-text-muted">#MASK-12345-678]: Subject</span>
					</div>
				</div>
			</div>
		</div>
	</div>

	{* ─────────────── Members roster ─────────────── *}
	<div id="{$form_id}Members">
		{* Roster for the JS control: every active worker + role in this group (1=member, 2=manager, 0=neither) *}
		{$worker_roster = []}
		{foreach from=$workers item=worker key=worker_id}
			{$role = 0}
			{if isset($members[$worker_id])}
				{if $members[$worker_id]->is_manager}{$role = 2}{else}{$role = 1}{/if}
			{/if}
			{$row = ['id' => $worker->id, 'name' => $worker->getName(), 'role' => $role]}
			{$worker_roster[] = $row}
		{/foreach}

		<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$form_id}MembersPanel">
			<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
				<div class="cerb-ui-header--title-sm">{'common.members'|devblocks_translate|capitalize} <span class="cerb-u-text-muted cerb-u-fw-400" data-cerb-group-count></span></div>
				<div class="cerb-ui-header--right">
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						<label class="cerb-ui-toggle">
							<input type="checkbox" id="groupMembersOnly_{$form_id}" data-cerb-group-members-only>
							<span class="cerb-ui-toggle--slider"></span>
						</label>
						<label for="groupMembersOnly_{$form_id}" class="cerb-u-text-muted cerb-u-fs-n1">Members only</label>
					</div>
					<label class="cerb-ui-form--control cerb-group-filter">
						<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-search"></span>
						<input type="text" data-cerb-group-filter placeholder="{'common.filter'|devblocks_translate|capitalize}" autocomplete="off" spellcheck="false">
					</label>
				</div>
			</div>

			<div class="cerb-group-roster" data-cerb-group-list>
				{* Sticky "set all" header — aligns over the per-row role column *}
				<div class="cerb-group-setall-row" data-cerb-group-setall>
					<div class="cerb-ui-switcher cerb-group-switcher">
						<button type="button" data-value="1" title="{'common.member'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-user"></span></button>
						<button type="button" data-value="2" title="{'common.manager'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-star"></span></button>
						<button type="button" data-value="0" title="{'common.neither'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-ban"></span></button>
					</div>
					<span class="cerb-u-text-muted cerb-u-fs-n1">Set all</span>
				</div>
			</div>
			<div class="cerb-u-p-2 cerb-u-text-muted" data-cerb-group-empty hidden>No members to show.</div>
		</div>
	</div>
</div>

{if !empty($group->id)}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert" style="display:none;" data-cerb-delete-confirm>
	<div class="cerb-ui-header">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-exclamation-mark cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">{'common.delete'|devblocks_translate|capitalize}</div>
				<div class="cerb-ui-header--subtitle">Are you sure you want to permanently delete this group?</div>
			</div>
		</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-cancel"><span class="cerb-icons cerb-icon-ban"></span> {'common.no'|devblocks_translate|capitalize}</button>
			<button type="button" class="cerb-ui-button delete"><span class="cerb-icons cerb-icon-trash"></span> {'common.yes'|devblocks_translate|capitalize}</button>
		</div>
	</div>

	{if !empty($destination_buckets)}
	<div class="cerb-u-mt-2">
		<div class="cerb-ui-form--label">Move records from this group's buckets to:</div>
		<table cellpadding="2" cellspacing="0" border="0">
			{$buckets = $group->getBuckets()}
			{foreach from=$buckets item=bucket}
			<tr>
				<td>{$bucket->name}</td>
				<td><span class="cerb-icons cerb-icon-right-arrow"></span></td>
				<td>
					<select name="move_deleted_buckets[{$bucket->id}]">
						{foreach from=$destination_buckets item=dest_buckets key=dest_group_id}
						{$dest_group = $groups.$dest_group_id}
							{foreach from=$dest_buckets item=dest_bucket}
							<option value="{$dest_bucket->id}">{$dest_group->name}: {$dest_bucket->name}</option>
							{/foreach}
						{/foreach}
					</select>
				</td>
			</tr>
			{/foreach}
		</table>
	</div>
	{/if}
</div>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($group->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>
</div>{* #routingAgentMount -- AgentPane wraps this *}

<style nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
.cerb-group-filter { width: 16em; max-width: 100%; }
.cerb-group-filter input {
	width: 100%;
	box-sizing: border-box;
	padding: 0.4em 0.6em 0.4em 2.2em;
	border: 1px solid var(--cerb-color-background-contrast-220);
	border-radius: 6px;
	background: var(--cerb-color-form-input-background);
	color: var(--cerb-color-text);
	outline: none;
}
.cerb-group-filter input:focus { border-color: var(--cerb-color-form-element-focus); }

.cerb-group-roster {
	max-height: 360px;
	overflow-y: auto;
	border: 1px solid var(--cerb-color-background-contrast-220);
	border-radius: 8px;
}
.cerb-group-row, .cerb-group-setall-row {
	display: flex;
	align-items: center;
	gap: 0.6em;
	padding: 0.3em 0.6em;
}
.cerb-group-row + .cerb-group-row { border-top: 1px solid var(--cerb-color-background-contrast-235); }
.cerb-group-row:hover { background: var(--cerb-color-background-contrast-250); }
.cerb-group-row[hidden] { display: none; }
.cerb-group-setall-row {
	position: sticky;
	top: 0;
	z-index: 1;
	background: var(--cerb-color-background-contrast-245);
	border-bottom: 1px solid var(--cerb-color-background-contrast-220);
}
.cerb-group-row--avatar { display: inline-flex; flex: 0 0 auto; }
.cerb-group-row--name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cerb-group-switcher { flex: 0 0 auto; }
.cerb-group-switcher button { padding: 0.25em 0.5em; min-height: 0; }
{/literal}
</style>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize}: {'common.group'|devblocks_translate|capitalize}");

		// Tabs
		$('#{$form_id}Tabs > ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Avatar
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Switchers (is_private) — write the bound hidden input on select
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}

		// Outgoing mail record choosers
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#replyAddressChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_ADDRESS}",
				name: 'reply_address_id',
				emptyIcon: 'mail',
				query: 'mailTransport.id:>0 isBanned:n isDefunct:n',
				searchPlaceholder: "{'common.send.from'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#replySignatureChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}",
				name: 'reply_signature_id',
				emptyIcon: 'signature',
				searchPlaceholder: "{'common.signature'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#replySigningKeyChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_GPG_PRIVATE_KEY}",
				name: 'reply_signing_key_id',
				emptyIcon: 'key',
				searchPlaceholder: "{'common.encrypt.signing.key'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			new CerbUI.RecordChooser($popup.find('#replyHtmlTemplateChooser_{$form_id}')[0], {
				context: "{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}",
				name: 'reply_html_template_id',
				emptyIcon: 'file',
				searchPlaceholder: "{'common.html_mail_template'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});
		}

		// Ticket masks toggle → reveal the subject prefix
		if(window.CerbUI && CerbUI.Toggle) {
			let maskToggle = $popup.find('#subjectHasMask_{$form_id}').closest('.cerb-ui-toggle')[0];
			if(maskToggle) {
				new CerbUI.Toggle(maskToggle, {
					onChange: function(checked) {
						let $prefix = $('#subjectPrefix_{$form_id}');
						if(checked) { $prefix.show().find('input:text').focus(); } else { $prefix.hide(); }
					}
				});
			}
		}

		// Members roster — filterable full list; posts ONLY changed rows (delta) so 1000+ workers stay cheap
		(function() {
			let panel = $popup.find('#{$form_id}MembersPanel')[0];
			if(!panel) return;

			let list = panel.querySelector('[data-cerb-group-list]');
			let filterInput = panel.querySelector('[data-cerb-group-filter]');
			let countEl = panel.querySelector('[data-cerb-group-count]');
			let emptyEl = panel.querySelector('[data-cerb-group-empty]');

			let workerCatalog = {$worker_roster|json_encode nofilter};

			let roleMeta = {
				'1': { icon: 'user', label: "{'common.member'|devblocks_translate|capitalize|escape:'javascript' nofilter}" },
				'2': { icon: 'star', label: "{'common.manager'|devblocks_translate|capitalize|escape:'javascript' nofilter}" },
				'0': { icon: 'ban',  label: "{'common.neither'|devblocks_translate|capitalize|escape:'javascript' nofilter}" }
			};

			let buildRow = function(w) {
				let role = String(w.role || 0);
				let row = document.createElement('div');
				row.className = 'cerb-group-row';
				row.setAttribute('data-worker-id', w.id);
				row.setAttribute('data-name', (w.name || '').toLowerCase());

				let sw = document.createElement('div');
				sw.className = 'cerb-ui-switcher cerb-group-switcher';
				sw.setAttribute('data-cerb-group-switcher', '');
				['1','2','0'].forEach(function(v) {
					let b = document.createElement('button');
					b.type = 'button';
					b.setAttribute('data-value', v);
					b.title = roleMeta[v].label;
					if(v === role) b.className = 'cerb-ui-switcher--active';
					b.innerHTML = '<span class="cerb-icons cerb-icon-' + roleMeta[v].icon + '"></span>';
					sw.appendChild(b);
				});
				row.appendChild(sw);

				let input = document.createElement('input');
				input.type = 'hidden';
				input.name = 'group_memberships[' + w.id + ']';
				input.value = role;
				input.setAttribute('data-orig', role);
				input.disabled = true; // disabled inputs don't POST — enabled only once changed
				row.appendChild(input);

				let avatarHost = document.createElement('span');
				avatarHost.className = 'cerb-group-row--avatar cerb-u-ml-2' + (role === '0' ? ' cerb-u-opacity-25' : '');
				if(window.CerbUI && CerbUI.Avatar)
					avatarHost.appendChild(CerbUI.Avatar.create({ label: w.name || '?', seed: 'worker:' + w.id, imageUrl: '', size: 22 }));
				row.appendChild(avatarHost);

				let name = document.createElement('a');
				name.className = 'cerb-peek-trigger cerb-u-fw-600 cerb-u-underline-hover cerb-group-row--name' + (role === '0' ? ' cerb-u-opacity-25' : '');
				name.setAttribute('data-context', 'worker');
				name.setAttribute('data-context-id', w.id);
				name.textContent = w.name || ('#' + w.id);
				row.appendChild(name);

				return row;
			};

			let frag = document.createDocumentFragment();
			workerCatalog.forEach(function(w) { frag.appendChild(buildRow(w)); });
			list.appendChild(frag);
			if(window.jQuery) jQuery(list).find('.cerb-peek-trigger').cerbPeekTrigger();

			let setRowValue = function(row, val) {
				val = String(val);
				let input = row.querySelector('input[type=hidden]');
				if(!input) return;
				input.value = val;
				input.disabled = (val === input.getAttribute('data-orig')); // unchanged → won't POST
				row.querySelectorAll('[data-cerb-group-switcher] button').forEach(function(b) {
					b.classList.toggle('cerb-ui-switcher--active', b.getAttribute('data-value') === val);
				});
				let dim = (val === '0');
				['.cerb-group-row--avatar', '.cerb-group-row--name'].forEach(function(sel) {
					let el = row.querySelector(sel);
					if(el) el.classList.toggle('cerb-u-opacity-25', dim);
				});
			};

			list.addEventListener('click', function(e) {
				let btn = e.target.closest('[data-cerb-group-switcher] button');
				if(!btn) return;
				e.stopPropagation();
				setRowValue(btn.closest('.cerb-group-row'), btn.getAttribute('data-value'));
			});

			let membersOnly = false;
			let rows = list.querySelectorAll('.cerb-group-row');

			// Visibility = text filter AND (members-only ? currently a member : true). Role edits don't re-filter.
			let applyFilter = function() {
				let term = filterInput ? filterInput.value.trim().toLowerCase() : '';
				let shown = 0;
				rows.forEach(function(r) {
					let matchesText = (term === '' || r.getAttribute('data-name').indexOf(term) !== -1);
					let isMember = (r.querySelector('input[type=hidden]').value !== '0');
					let visible = matchesText && (!membersOnly || isMember);
					r.hidden = !visible;
					if(visible) shown++;
				});
				if(countEl) countEl.textContent = (shown === rows.length) ? ('(' + rows.length + ')') : ('(' + shown + ' of ' + rows.length + ')');
				if(emptyEl) emptyEl.hidden = (shown !== 0);
			};

			if(filterInput) filterInput.addEventListener('input', applyFilter);

			let membersToggle = panel.querySelector('[data-cerb-group-members-only]');
			if(membersToggle && window.CerbUI && CerbUI.Toggle) {
				new CerbUI.Toggle(membersToggle.closest('.cerb-ui-toggle'), {
					onChange: function(checked) { membersOnly = checked; applyFilter(); }
				});
			}
			applyFilter();

			let setAll = panel.querySelector('[data-cerb-group-setall]');
			if(setAll) {
				setAll.addEventListener('click', function(e) {
					let btn = e.target.closest('button[data-value]');
					if(!btn) return;
					e.stopPropagation();
					let val = btn.getAttribute('data-value');
					rows.forEach(function(r) { if(!r.hidden) setRowValue(r, val); });
				});
			}
		})();

		// ── Routing editor (Mail: Incoming) — CerbUI.KataEditor ──

		let autocomplete_suggestions = {if $autocomplete_json}{$autocomplete_json nofilter}{else}[]{/if};

		let editor = new CerbUI.KataEditor($popup.find('textarea[name=routing_kata]')[0], {
			onAutocomplete: CerbUI.KataEditor.kataFieldSource(autocomplete_suggestions),
			// Mark what changed since the last save. The checkpoint is captured on open and re-taken on save,
			// so the gutter answers "what have we touched in this sitting" -- whether the edit came from a
			// person or from the agent, which has no other way to show its work.
			diffGutter: true,
			toolbar: {
				sections: [ $popup.find('[data-cerb-bucket-routing-toolbar-items]')[0] ].filter(Boolean),
				onAction: function(value, ed, item) {
					if(value === 'suggest')    { ed.openAutocomplete(); return true; }
					{if $group->id}
					if(value === 'changesets') { openChangesets(); return true; }
					{/if}
					if(value === 'help')   { $popup.find('[data-cerb-fieldset-help]').toggle(!!(item && item.pressed)); return true; }
					if(value === 'tester') { $popup.find('[data-cerb-routing-tester]').toggle(!!(item && item.pressed)); return true; }
					return false;
				}
			}
		});

		{if $group->id}
		let openChangesets = function() {
			let formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'records');
			formData.set('action', 'showChangesetsPopup');
			formData.set('record_type', 'group_routing');
			formData.set('record_id', '{$group->id}');
			formData.set('record_key', 'routing_kata');

			let $editor_kata_differ_popup = genericAjaxPopup('editorDiff{$form_id}', formData, null, null, '80%');

			$editor_kata_differ_popup.one('cerb-diff-viewer-ready', function(e) {
				e.stopPropagation();

				if(!e.hasOwnProperty('viewer'))
					return;

				e.viewer.setCurrent(editor.getValue());

				e.viewer.onRestore(function(content) {
					editor.setValue(content);
					editor.clearSelection();
				});
			});
		};
		{/if}

		$popup.on('peek_saved', function() { if(typeof editor.resetDiffBaseline === 'function') editor.resetDiffBaseline(); });

		{include file="devblocks:cerberusweb.core::records/types/mail_routing_rule/_agent_pane.tpl" routing_scope="group" mount="routingAgentMount`$form_id`" split=true}

		let $tab_routing = $('#{$form_id}Inbox');
		let $fieldset_tester = $tab_routing.find('[data-cerb-routing-tester]');
		let $fieldset_tester_results = $fieldset_tester.find('[data-cerb-routing-tester-results]');

		let editor_tester = new CerbUI.KataEditor($fieldset_tester.find('textarea[name="tester[placeholders]"]')[0]);

		$fieldset_tester.find('.cerb-code-editor-toolbar-button--chooser')
			.attr('data-interaction-uri', 'cerb:automation:ai.cerb.routingRuleBuilder.inputChooser')
			.attr('data-interaction-params', '')
			.cerbBotTrigger({
				'width': '80%',
				'done': function(e) {
					Devblocks.interactionWorkerPostActions(e.eventData, editor_tester);
				},
			})
		;

		$fieldset_tester.find('.cerb-code-editor-toolbar-button--run').on('click', function(e) {
			e.stopPropagation();

			editor.clearHighlight();

			$fieldset_tester_results.html('').hide();

			let formData = new FormData($frm.get(0));
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'mail_routing_rule');
			formData.set('action', 'testRoutingKataJson');

			genericAjaxPost(formData, null, null, function(json) {
				if('object' !== typeof json)
					return;

				if(!json.hasOwnProperty('key')) {
					$fieldset_tester_results.append($('<div/>').addClass('cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note').text('(no matching rules)')).fadeIn();

				} else if(json.hasOwnProperty('line')) {
					let row = json['line'];
					editor.highlightLine(row, { color: 'green' });
					editor.scrollToLine(row);

					$fieldset_tester_results.append($('<div/>').addClass('cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--success').text('Matched ' + json['key'])).fadeIn();
				}
			});
		});
	});
});
</script>
