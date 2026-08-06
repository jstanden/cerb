{$peek_context = CerberusContexts::CONTEXT_TICKET}
{$peek_context_id = $ticket->id}
{$form_id = uniqid()}

{$ticket_org = $ticket->getOrg()}
{$owner = $ticket->getOwner()}
{$cur_group = $groups[$ticket->group_id]}
{$cur_bucket = $buckets[$ticket->bucket_id]}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmTicketPeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="ticket">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$ticket->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		{* Subject *}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'ticket.subject'|devblocks_translate|capitalize}</label>
			<input type="text" name="subject" maxlength="255" value="{$ticket->subject}" autofocus="autofocus">
		</div>

		{* Group/Bucket — a nested type-to-filter menu (groups → their buckets) *}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.bucket'|devblocks_translate|capitalize}</label>
			<div>
				<input type="hidden" name="group_id" id="groupId_{$form_id}" value="{$ticket->group_id}">
				<input type="hidden" name="bucket_id" id="bucketId_{$form_id}" value="{$ticket->bucket_id}">

				<button type="button" class="cerb-ui-button cerb-ui-button--subtle cerb-u-flex cerb-u-items-center cerb-u-gap-1" id="bucketTrigger_{$form_id}" data-group-id="{$ticket->group_id}" data-group-label="{if $cur_group}{$cur_group->name}{/if}" data-avatar="{devblocks_url}c=avatars&context=group&context_id={$ticket->group_id}{/devblocks_url}">
					<span data-cerb-bucket-icon class="cerb-u-flex cerb-u-items-center"></span>
					<span data-cerb-bucket-label>{if $cur_group}{$cur_group->name}{/if}{if $cur_bucket} &rsaquo; {$cur_bucket->name}{/if}</span>
					<span class="cerb-icons cerb-icon-chevron-down"></span>
				</button>

				<ul id="bucketMenu_{$form_id}" hidden>
					{foreach from=$groups item=group key=group_id}
						<li data-group-id="{$group_id}" data-group-label="{$group->name}" data-avatar="{devblocks_url}c=avatars&context=group&context_id={$group_id}{/devblocks_url}">{$group->name}
							<ul>
								{foreach from=$buckets item=bucket key=bucket_id}
									{if $bucket->group_id == $group_id}
										<li data-group-id="{$group_id}" data-bucket-id="{$bucket_id}" data-group-label="{$group->name}" data-group-avatar="{devblocks_url}c=avatars&context=group&context_id={$group_id}{/devblocks_url}">{$bucket->name}</li>
									{/if}
								{/foreach}
							</ul>
						</li>
					{/foreach}
				</ul>
			</div>
		</div>

		{* Status + Importance *}
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field" style="flex:2 1 14em;min-width:0;">
				<label class="cerb-ui-form--label">{'common.status'|devblocks_translate|capitalize}</label>
				<div>
					<input type="hidden" name="status_id" id="statusId_{$form_id}" value="{$ticket->status_id}">
					<div class="cerb-ui-switcher" data-cerb-input="statusId_{$form_id}" id="statusSwitcher_{$form_id}">
						<button type="button" data-value="{Model_Ticket::STATUS_OPEN}"{if $ticket->status_id == Model_Ticket::STATUS_OPEN} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-play-button"></span> {'status.open'|devblocks_translate|capitalize}</button>
						<button type="button" data-value="{Model_Ticket::STATUS_WAITING}"{if $ticket->status_id == Model_Ticket::STATUS_WAITING} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-clock"></span> {'status.waiting'|devblocks_translate|capitalize}</button>
						{if $active_worker->hasPriv('core.ticket.actions.close') || ($ticket->status_id == Model_Ticket::STATUS_CLOSED)}<button type="button" data-value="{Model_Ticket::STATUS_CLOSED}"{if $ticket->status_id == Model_Ticket::STATUS_CLOSED} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'status.closed'|devblocks_translate|capitalize}</button>{/if}
						{if $active_worker->hasPriv("contexts.{$peek_context}.delete") || ($ticket->status_id == Model_Ticket::STATUS_DELETED)}<button type="button" data-value="{Model_Ticket::STATUS_DELETED}"{if $ticket->status_id == Model_Ticket::STATUS_DELETED} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-trash"></span> {'status.deleted'|devblocks_translate|capitalize}</button>{/if}
					</div>

					<div id="ticketReopen_{$form_id}" class="cerb-u-mt-2" style="display:{if $ticket->status_id == Model_Ticket::STATUS_WAITING}block{else}none{/if};">
						<label class="cerb-ui-form--label">{'common.reopen_at'|devblocks_translate}</label>
						<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
							<input type="text" name="ticket_reopen" class="input_date" style="flex:1 1 auto;min-width:0;" value="{if !empty($ticket->reopen_at)}{$ticket->reopen_at|devblocks_date}{/if}">
						</div>
						<div class="cerb-ui-form--help">{'display.reply.next.resume_blank'|devblocks_translate}</div>
					</div>
				</div>
			</div>

			<div class="cerb-ui-form--field" style="flex:1 1 10em;min-width:0;">
				<label class="cerb-ui-form--label">{'common.importance'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-slider" id="importanceSlider_{$form_id}">
					<input type="hidden" name="importance" value="{$ticket->importance|default:0}">
				</div>
			</div>
		</div>

		{* Spam Training *}
		{if '' == $ticket->spam_training && $active_worker->hasPriv('core.ticket.actions.spam')}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Spam Training</label>
				<div>
					<input type="hidden" name="spam_training" id="spamTraining_{$form_id}" value="{$field_overrides.spam_training}">
					<div class="cerb-ui-switcher" data-cerb-input="spamTraining_{$form_id}">
						<button type="button" data-value=""{if !$field_overrides.spam_training} class="cerb-ui-switcher--active"{/if}>Unknown</button>
						<button type="button" data-value="S"{if 'S' == $field_overrides.spam_training} class="cerb-ui-switcher--active"{/if}>Spam</button>
						<button type="button" data-value="N"{if 'N' == $field_overrides.spam_training} class="cerb-ui-switcher--active"{/if}>Not Spam</button>
					</div>
				</div>
			</div>
		{/if}

		{* Org + Owner *}
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.organization'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="orgChooser_{$form_id}">
					{if $ticket_org}
						<li data-context-id="{$ticket_org->id}" data-label="{$ticket_org->name}" data-image="{devblocks_url}c=avatars&context=org&context_id={$ticket_org->id}{/devblocks_url}?v={$ticket_org->updated}"></li>
					{/if}
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="ownerChooser_{$form_id}">
					{if $owner}
						<li data-context-id="{$owner->id}" data-label="{$owner->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"></li>
					{/if}
				</div>
			</div>
		</div>

		{* Participants *}
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.participants'|devblocks_translate|capitalize}</label>
			<div class="cerb-ui-record-chooser" id="participantsChooser_{$form_id}">
				{if !empty($requesters)}
					{foreach from=$requesters item=requester}
						<li data-context-id="{$requester->id}" data-label="{$requester->getNameWithEmail()}" data-image="{devblocks_url}c=avatars&context=address&context_id={$requester->id}{/devblocks_url}?v={$requester->updated}"></li>
					{/foreach}
				{/if}
			</div>
		</div>

		{* Custom fields (cerb-ui renderer) *}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET context_id=$ticket->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save" {if $pref_keyboard_shortcuts}title="(Shift+Enter)"{/if}><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frmTicketPeek');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function() {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.ticket'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Save
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);

		{if $pref_keyboard_shortcuts}
		$frm.keypress(function(e) {
			if(e.shiftKey && 13 === e.which) {
				e.preventDefault();
				e.stopPropagation();
				$popup.find('button.save').focus();
			}
		});
		{/if}

		// Abstract peeks (chips link to record cards)
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Record choosers

		let orgChooser = null, ownerChooser = null;

		if(window.CerbUI && CerbUI.RecordChooser) {
			orgChooser = new CerbUI.RecordChooser($popup.find('#orgChooser_{$form_id}')[0], {
				context: 'org',
				name: 'org_id',
				emptyIcon: 'building-office',
				searchPlaceholder: "{'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
				{if $active_worker->hasPriv('contexts.cerberusweb.contexts.org.create')}create: 'if-null',{/if}
			});

			ownerChooser = new CerbUI.RecordChooser($popup.find('#ownerChooser_{$form_id}')[0], {
				context: 'worker',
				name: 'owner_id',
				emptyIcon: 'user',
				query: 'group:(id:' + $popup.find('#groupId_{$form_id}').val() + ')',
				searchPlaceholder: "{'common.owner'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
			});

			new CerbUI.RecordChooser($popup.find('#participantsChooser_{$form_id}')[0], {
				context: 'address',
				name: 'participants',
				multiple: true,
				emptyIcon: 'mail',
				searchPlaceholder: "{'common.participants'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
				{if $active_worker->hasPriv('contexts.cerberusweb.contexts.address.create')}create: true,{/if}
			});
		}

		// Bucket menu (groups → buckets, type-to-filter)
		let $bucketLabel = $popup.find('[data-cerb-bucket-label]');
		let $bucketIcon = $popup.find('[data-cerb-bucket-icon]');
		let $bucketTrigger = $popup.find('#bucketTrigger_{$form_id}');
		let bucketMenuUl = $popup.find('#bucketMenu_{$form_id}')[0];

		// Render a group's profile icon (monogram falls back automatically if there's no avatar)
		let setBucketIcon = function(label, gid, imageUrl) {
			if(!(window.CerbUI && CerbUI.Avatar)) return;
			let av = CerbUI.Avatar.create({ label: label || '?', seed: 'group:' + gid, imageUrl: imageUrl || '', size: 18 });
			av.style.marginRight = '0.4em';
			$bucketIcon.empty().append(av);
		};

		// Seed the trigger icon from the ticket's current group
		if($bucketTrigger.attr('data-group-id'))
			setBucketIcon($bucketTrigger.attr('data-group-label'), $bucketTrigger.attr('data-group-id'), $bucketTrigger.attr('data-avatar'));

		if(bucketMenuUl && window.CerbUI && CerbUI.Menu) {
			let bucketMenu = new CerbUI.Menu(bucketMenuUl, {
				filter: true,
				panelClass: 'cerb-bucket-menu',
				onRenderItem: function(li, src) {
					let gid = src.getAttribute('data-group-id');
					if(!gid || !(window.CerbUI && CerbUI.Avatar)) return;

					// Filtered (flattened) match → avatar + two-line cell (group eyebrow over bucket)
					if(li.classList.contains('cerb-ui-menu--item-pathed')) {
						let groupLabel = src.getAttribute('data-group-label') || '';
						let bucketName = (src.textContent || '').trim();
						li.querySelectorAll('.cerb-ui-menu--label, .cerb-ui-menu--path').forEach(function(n) { n.remove(); });

						let av = CerbUI.Avatar.create({ label: groupLabel || bucketName, seed: 'group:' + gid, imageUrl: src.getAttribute('data-group-avatar') || '', size: 22 });
						av.classList.add('cerb-bucket-menu--avatar');
						li.insertBefore(av, li.firstChild);

						let stack = document.createElement('span');
						stack.className = 'cerb-bucket-menu--text';
						let eyebrow = document.createElement('span');
						eyebrow.className = 'cerb-bucket-menu--eyebrow';
						eyebrow.textContent = groupLabel;
						let main = document.createElement('span');
						main.className = 'cerb-bucket-menu--main';
						main.textContent = bucketName;
						stack.appendChild(eyebrow);
						stack.appendChild(main);
						li.appendChild(stack);
						return;
					}

					// Cascade view: group profile icon on the first-level (group) rows only
					if(src.hasAttribute('data-bucket-id')) return;
					let av2 = CerbUI.Avatar.create({ label: src.getAttribute('data-group-label') || '?', seed: 'group:' + gid, imageUrl: src.getAttribute('data-avatar') || '', size: 18 });
					av2.style.marginRight = '0.5em';
					li.insertBefore(av2, li.firstChild);
				},
				onSelect: function(li, src) {
					let bid = src.getAttribute('data-bucket-id');
					if(bid === null) return; // a group header, not a bucket
					let gid = src.getAttribute('data-group-id');
					$popup.find('#groupId_{$form_id}').val(gid);
					$popup.find('#bucketId_{$form_id}').val(bid);
					$bucketLabel.text((src.getAttribute('data-group-label') || '') + ' › ' + (src.textContent || '').trim());
					setBucketIcon(src.getAttribute('data-group-label'), gid, src.getAttribute('data-group-avatar'));
					$frm.trigger('cerb-form-update');
				}
			});

			$bucketTrigger.on('click', function(e) {
				e.stopPropagation();
				bucketMenu.isOpen() ? bucketMenu.close() : bucketMenu.open(this);
			});
		}

		// Status switcher → reveal the reopen-at date for 'waiting' only
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				let isStatus = (this.id === 'statusSwitcher_{$form_id}');

				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) {
						if(input) input.value = value;

						if(isStatus) {
							// Only 'waiting' carries a scheduled reopen-at date
							if(value === '{Model_Ticket::STATUS_WAITING}') {
								$('#ticketReopen_{$form_id}').stop(true,true).fadeIn();
							} else {
								$('#ticketReopen_{$form_id}').stop(true,true).fadeOut();
							}
						}
					}
				});
			});
		}

		// Importance slider
		if(window.CerbUI && CerbUI.Slider) {
			let importanceEl = $popup.find('#importanceSlider_{$form_id}')[0];
			if(importanceEl)
				new CerbUI.Slider(importanceEl, { min: 0, max: 100, step: 1, midpoint: 50 });
		}

		// Dates
		$frm.find('input.input_date').each(function() { if(window.CerbUI && CerbUI.DatePicker) new CerbUI.DatePicker.FormInput(this); });

		// When the group changes, re-scope the owner chooser to that group's members
		$frm.on('cerb-form-update', function() {
			let group_id = $popup.find('#groupId_{$form_id}').val();
			if(ownerChooser) ownerChooser.setQuery('group:(id:' + group_id + ')');
		});

		{if $focus_submit}
		$frm.find('button.save').focus();
		{else}
		$frm.find('input[name=subject]').focus();
		{/if}
	});
});
</script>
