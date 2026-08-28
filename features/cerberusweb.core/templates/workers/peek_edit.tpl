{$peek_context = CerberusContexts::CONTEXT_WORKER}
{$peek_context_id = $worker->id}
{$form_id = "frmWorkerEdit{uniqid()}"}

{$is_self = ($active_worker->id == $worker->id)}

{* The dialog's title as DOM data rather than a string emitted into the <script> below. Smarty escapes an
   attribute correctly on its own; a translated name reaching a JS string literal needs `|escape:'javascript'`
   and is silently broken the first time someone forgets it. *}
{if $worker->id}
	{$popup_title = "{'common.edit'|devblocks_translate|capitalize}: {$worker->getName()}"}
{else}
	{$popup_title = "{'common.create'|devblocks_translate|capitalize}: {'common.worker'|devblocks_translate|capitalize}"}
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" data-cerb-dialog-title="{$popup_title}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worker">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$worker->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="{$form_id}TabsWrap">
	<ul id="{$form_id}Tabs">
		<li data-alias="profile"><a href="#{$form_id}Profile">{'common.profile'|devblocks_translate|capitalize}</a></li>
		<li data-alias="ai" data-cerb-tab-ai><a href="#{$form_id}Ai">{'worker.is_ai'|devblocks_translate}</a></li>
		<li data-alias="groups"><a href="#{$form_id}Groups">{'common.groups'|devblocks_translate|capitalize}</a></li>
		<li data-alias="login" data-cerb-tab-login><a href="#{$form_id}Login">{'common.authentication'|devblocks_translate|capitalize}</a></li>
		<li data-alias="localization"><a href="#{$form_id}Localization">{'common.localization'|devblocks_translate|capitalize}</a></li>
		<li data-alias="availability"><a href="#{$form_id}Availability">{'common.availability'|devblocks_translate|capitalize}</a></li>
	</ul>

	{* ─────────────── Profile ─────────────── *}
	<div id="{$form_id}Profile">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{* Type + Role

				   Role is ONE three-way control over two columns: Inactive writes is_disabled=1 AND
				   is_superuser=0, so "inactive admin" is unrepresentable rather than merely discouraged --
				   reactivating means picking a role again (least privilege on the way back in). *}
				{if $worker->is_disabled}{$worker_role = 'inactive'}{elseif $worker->is_superuser}{$worker_role = 'admin'}{else}{$worker_role = 'worker'}{/if}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'worker.type'|devblocks_translate|capitalize}</label>
						{if $is_self}
							<input type="hidden" name="is_ai" value="{$worker->is_ai}">
							<div class="cerb-u-text-muted">{if $worker->is_ai}{'worker.is_ai'|devblocks_translate}{else}{'worker.type.human'|devblocks_translate|capitalize}{/if}</div>
						{else}
							<div>
								<input type="hidden" name="is_ai" id="isAi_{$form_id}" value="{$worker->is_ai}">
								<div class="cerb-ui-switcher" data-cerb-input="isAi_{$form_id}" data-cerb-worker-type>
									<button type="button" data-value="0"{if !$worker->is_ai} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-user"></span> {'worker.type.human'|devblocks_translate|capitalize}</button>
									<button type="button" data-value="1"{if $worker->is_ai} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-bot"></span> {'worker.is_ai'|devblocks_translate}</button>
								</div>
							</div>
						{/if}
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'worker.role'|devblocks_translate|capitalize}</label>
						{if $is_self}
							{* You can't deactivate or demote yourself; DAO_Worker::onBeforeUpdateByActor() enforces it *}
							<input type="hidden" name="is_disabled" value="{$worker->is_disabled}">
							<input type="hidden" name="is_superuser" value="{$worker->is_superuser}">
							<div class="cerb-u-text-muted">{if $worker->is_disabled}{'common.inactive'|devblocks_translate|capitalize}{elseif $worker->is_superuser}{'worker.is_superuser'|devblocks_translate|capitalize}{else}{'common.worker'|devblocks_translate|capitalize}{/if}</div>
						{else}
							<div>
								<input type="hidden" name="is_disabled" id="isDisabled_{$form_id}" value="{$worker->is_disabled}">
								<input type="hidden" name="is_superuser" id="isSuperuser_{$form_id}" value="{$worker->is_superuser}">
								<div class="cerb-ui-switcher" data-cerb-worker-role data-cerb-role-disabled="isDisabled_{$form_id}" data-cerb-role-superuser="isSuperuser_{$form_id}" data-cerb-role-value="{$worker_role}">
									<button type="button" data-value="inactive"{if $worker_role == 'inactive'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> {'common.inactive'|devblocks_translate|capitalize}</button>
									<button type="button" data-value="worker"{if $worker_role == 'worker'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-user"></span> {'common.worker'|devblocks_translate|capitalize}</button>
									<button type="button" data-value="admin"{if $worker_role == 'admin'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-shield"></span> {'worker.is_superuser'|devblocks_translate|capitalize}</button>
								</div>
							</div>
						{/if}
					</div>
				</div>

				{* First + Last name *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.name.first'|devblocks_translate|capitalize}</label>
						<input type="text" name="first_name" value="{$worker->first_name}" autofocus="autofocus">
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.name.last'|devblocks_translate|capitalize}</label>
						<input type="text" name="last_name" value="{$worker->last_name}">
					</div>
				</div>

				{* Aliases *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.aliases'|devblocks_translate|capitalize} <span class="cerb-ui-form--hint">(press Enter to add)</span></label>
					<div class="cerb-ui-tag-input" id="aliasesInput_{$form_id}" data-name="aliases">
						{foreach from=$aliases item=alias_val}
							<input type="text" name="aliases[]" maxlength="255" value="{$alias_val}">
						{/foreach}
					</div>
				</div>

				{* Photo + Gender *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.photo'|devblocks_translate|capitalize}</label>
						<div class="cerb-worker-photo cerb-u-flex cerb-u-items-center cerb-u-gap-2">
							<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
								data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}" data-name="avatar_image"
								data-avatar="{$worker->getName()}" data-avatar-seed="worker:{$worker->id}"
								data-avatar-image="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}"></span>
							<input type="hidden" name="avatar_image" value="">
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.gender'|devblocks_translate|capitalize}</label>
						<div>
							<input type="hidden" name="gender" id="gender_{$form_id}" value="{$worker->gender}">
							<div class="cerb-ui-switcher" data-cerb-input="gender_{$form_id}">
								<button type="button" data-value="M"{if $worker->gender == 'M'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-gender-male"></span> {'common.gender.pronouns.male'|devblocks_translate}</button>
								<button type="button" data-value="F"{if $worker->gender == 'F'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-gender-female"></span> {'common.gender.pronouns.female'|devblocks_translate}</button>
								<button type="button" data-value=""{if empty($worker->gender)} class="cerb-ui-switcher--active"{/if}>{'common.gender.pronouns.neutral'|devblocks_translate}</button>
							</div>
						</div>
					</div>
				</div>

				{* Title + @mention *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'worker.title'|devblocks_translate|capitalize}</label>
						<input type="text" name="title" value="{$worker->title}">
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'worker.at_mention_name'|devblocks_translate}</label>
						<label class="cerb-ui-form--control">
							<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mention"></span>
							<input type="text" name="at_mention_name" value="{$worker->at_mention_name}" placeholder="UserNickname">
						</label>
					</div>
				</div>

				{* Email + Alternate emails *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.email'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="emailChooser_{$form_id}">
							{$addy = $worker->getEmailModel()}
							{if $addy}
								<li data-context-id="{$addy->id}" data-label="{$addy->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}"></li>
							{/if}
						</div>
					</div>

					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.emails.alternate'|devblocks_translate|capitalize}</label>
						<div class="cerb-ui-record-chooser" id="altEmailChooser_{$form_id}">
							{$addys = $worker->getEmailModels()}
							{if is_array($addys)}
								{foreach from=$addys item=alt_addy}
									{if $alt_addy->id != $worker->email_id}
										<li data-context-id="{$alt_addy->id}" data-label="{$alt_addy->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$alt_addy->id}{/devblocks_url}?v={$alt_addy->updated}"></li>
									{/if}
								{/foreach}
							{/if}
						</div>
					</div>
				</div>

				{* Phone + Mobile *}
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.phone'|devblocks_translate|capitalize}</label>
						<label class="cerb-ui-form--control">
							<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-phone-handset"></span>
							<input type="text" name="phone" value="{$worker->phone}" autocomplete="off" spellcheck="false">
						</label>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.mobile'|devblocks_translate|capitalize}</label>
						<label class="cerb-ui-form--control">
							<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mobile"></span>
							<input type="text" name="mobile" value="{$worker->mobile}" autocomplete="off" spellcheck="false">
						</label>
					</div>
				</div>

				{* Location + DOB — hidden for an AI (meaningless), but the values still post unchanged *}
				<div class="cerb-ui-form--row" data-cerb-field-human>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.location'|devblocks_translate|capitalize}</label>
						<label class="cerb-ui-form--control">
							<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"></span>
							<input type="text" name="location" value="{$worker->location}" autocomplete="off" spellcheck="false">
						</label>
					</div>
					<div class="cerb-ui-form--field">
						<label class="cerb-ui-form--label">{'common.dob'|devblocks_translate|capitalize}</label>
						<label class="cerb-ui-form--control">
							<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-calendar"></span>
							<input type="text" name="dob" value="{if $worker->dob}{$worker->dob}{/if}" autocomplete="off" spellcheck="false">
						</label>
					</div>
				</div>

				{* Custom fields (cerb-ui renderer) *}
				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
				{/if}
			</div>
		</div>

		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_WORKER context_id=$worker->id}
	</div>

	{* ─────────────── AI ───────────────

	   Everything here is one `agent.config_kata` blob on the `agent` satellite, not `worker` columns. The
	   panels are a view over it, server-rendered like the rest of this form: each control posts its own field
	   and `Cerb\Agent\Config::fromForm()` rebuilds the tree on save. Keys the form doesn't render are carried
	   through untouched, so hand-authored config survives a visit to this tab.

	   A rail rather than a stack of expandable rows: what a surface OFFERS is the same everywhere, so inviting
	   a reader to compare two surfaces was inviting them to read the same thing twice. The comparison worth
	   making is Defaults against one surface, which is what each surface panel shows inline. The pip is the
	   whole status: green runs here, gray doesn't. *}
	{$agent_has_namespaces = ($agent_scope_global.terminal|count > 0)}
	<div id="{$form_id}Ai" class="cerb-agent-config">
		<div class="cerb-ui-sidebar-layout cerb-u-items-start">
			<aside class="cerb-ui-sidebar" id="agentRail_{$form_id}" style="--cerb-ui-sidebar-width:210px;">
				<div class="cerb-ui-sidebar--body">
					<div class="cerb-ui-sidebar--section">
						<ul>
							<li data-target="_global" data-icon="bot">Everywhere</li>
						</ul>
					</div>

					<div class="cerb-ui-sidebar--section">
						<div class="cerb-ui-sidebar--label">Surfaces</div>
						<ul>
							{foreach from=$agent_surfaces key=surface_key item=surface_meta}
								<li data-target="{$surface_key}" data-pip="{if $agent_enabled[$surface_key]}green{else}gray{/if}">{$surface_meta.label}</li>
							{/foreach}
						</ul>
					</div>

					<div class="cerb-ui-sidebar--section">
						<div class="cerb-ui-sidebar--label">Events</div>
						<div class="cerb-ui-sidebar--content cerb-u-text-muted cerb-u-fs-n1">Reacting to an @mention, an assignment, or a new message is coming. Today an agent runs where a worker opens it.</div>
					</div>
				</div>
			</aside>

			<div class="cerb-ui-sidebar-layout--content">
				<div class="cerb-ui-panel cerb-ui-panel--spaced" data-cerb-agent-panel="_global">
					<div class="cerb-ui-header cerb-ui-header--tight">
						<div class="cerb-ui-header--title-sm">Everywhere</div>
						<div class="cerb-ui-header--subtitle">What this agent brings everywhere it runs. A surface adds to it; only the model query and the chat it runs get replaced.</div>
					</div>
					{include file="devblocks:cerberusweb.core::workers/_agent_scope.tpl" agent_scope=$agent_scope_global prefix='agent' surface='' surface_meta=[] has_namespaces=$agent_has_namespaces uid='global'}
				</div>

				{foreach from=$agent_surfaces key=surface_key item=surface_meta}
					<div class="cerb-ui-panel cerb-ui-panel--spaced{if !$agent_enabled[$surface_key]} cerb-agent-panel--off{/if}" data-cerb-agent-panel="{$surface_key}" hidden>
						<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
							<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
								{* The hidden input is what POSTS -- a checkbox sends nothing when it's off, and "off"
								   is a state this form has to state, since it keeps the block and its config. *}
								<input type="hidden" name="agent[components][{$surface_key}][enabled]" id="agentEnabled_{$surface_key}_{$form_id}" value="{if $agent_enabled[$surface_key]}1{else}0{/if}">
								<label class="cerb-ui-toggle cerb-u-flex-shrink-0">
									<input type="checkbox" data-cerb-agent-enabled="{$surface_key}"{if $agent_enabled[$surface_key]} checked="checked"{/if}>
									<span class="cerb-ui-toggle--slider"></span>
								</label>
								<div>
									<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-{$surface_meta.icon}"></span> {$surface_meta.label}</div>
									<div class="cerb-ui-header--subtitle">{$surface_meta.description}</div>
								</div>
							</div>
						</div>
						{include file="devblocks:cerberusweb.core::workers/_agent_scope.tpl" agent_scope=$agent_scope_surfaces[$surface_key] prefix="agent[components][{$surface_key}]" surface=$surface_key surface_meta=$surface_meta has_namespaces=$agent_has_namespaces uid=$surface_key}
					</div>
				{/foreach}
			</div>
		</div>
	</div>

	{* ─────────────── Groups ─────────────── *}
	<div id="{$form_id}Groups">
		{* Roster for the JS control: every group + this worker's current role (1=member, 2=manager, 0=neither) *}
		{if $worker->id}{$worker_groups = $worker->getMemberships()}{else}{$worker_groups = []}{/if}
		{$group_roster = []}
		{foreach from=$groups item=group key=group_id}
			{$role = 0}
			{if isset($worker_groups[$group_id])}
				{if $worker_groups[$group_id]->is_manager}{$role = 2}{else}{$role = 1}{/if}
			{/if}
			{capture assign=group_avatar_url}{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}{/capture}
			{$row = ['id' => $group->id, 'name' => $group->name, 'role' => $role, 'image' => $group_avatar_url]}
			{$group_roster[] = $row}
		{/foreach}

		<div class="cerb-ui-panel cerb-ui-panel--spaced" id="{$form_id}GroupsPanel">
			<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
				<div class="cerb-ui-header--title-sm">{'common.groups'|devblocks_translate|capitalize} <span class="cerb-u-text-muted cerb-u-fw-400" data-cerb-group-count></span></div>
				<div class="cerb-ui-header--right cerb-u-flex cerb-u-items-center cerb-u-gap-2">
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
			<div class="cerb-u-p-2 cerb-u-text-muted" data-cerb-group-empty hidden>No groups to show.</div>
		</div>
	</div>

	{* ─────────────── Authentication ─────────────── *}
	<div id="{$form_id}Login">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">{'common.authentication'|devblocks_translate|capitalize}</div>
			</div>

			<div class="cerb-ui-form">
				{* Password *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.password'|devblocks_translate|capitalize}</label>
					<div>
						<input type="hidden" name="is_password_disabled" id="isPasswordDisabled_{$form_id}" value="{$worker->is_password_disabled}">
						<div class="cerb-ui-switcher" data-cerb-input="isPasswordDisabled_{$form_id}">
							<button type="button" data-value="0"{if !$worker->is_password_disabled} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-key"></span> {'common.enabled'|devblocks_translate|capitalize}</button>
							<button type="button" data-value="1"{if $worker->is_password_disabled} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-ban"></span> {'common.disabled'|devblocks_translate|capitalize} (SSO only)</button>
						</div>
					</div>
				</div>

				{* MFA *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.auth.mfa'|devblocks_translate|capitalize}</label>
					<div>
						<input type="hidden" name="is_mfa_required" id="isMfaRequired_{$form_id}" value="{$worker->is_mfa_required}">
						<div class="cerb-ui-switcher" data-cerb-input="isMfaRequired_{$form_id}">
							<button type="button" data-value="1"{if $worker->is_mfa_required} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-shield"></span> {'common.required'|devblocks_translate|capitalize}</button>
							<button type="button" data-value="0"{if !$worker->is_mfa_required} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-unlock"></span> {'common.optional'|devblocks_translate|capitalize}</button>
						</div>
					</div>
				</div>

				{* Timeout *}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Timeout</label>
					<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
						Consider idle after
						<input type="text" name="timeout_idle_secs" value="{$worker->timeout_idle_secs}" maxlength="7" size="6" style="flex:0 0 auto;width:6em;">
						seconds of inactivity.
					</div>
				</div>
			</div>
		</div>
	</div>

	{* ─────────────── Localization ─────────────── *}
	<div id="{$form_id}Localization">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--row">
					<div class="cerb-ui-form--field" style="flex:1 1 calc(50% - 0.5em);min-width:13em;">
						<label class="cerb-ui-form--label">{'common.language'|devblocks_translate|capitalize}</label>
						<select name="lang_code" data-cerb-worker-selectmenu>
							{foreach from=$languages key=lang_code item=lang_name}
							<option value="{$lang_code}" {if $worker->language==$lang_code}selected="selected"{/if}>{$lang_name}</option>
							{/foreach}
						</select>
					</div>

					<div class="cerb-ui-form--field" style="flex:1 1 calc(50% - 0.5em);min-width:13em;">
						<label class="cerb-ui-form--label">{'common.timezone'|devblocks_translate|capitalize}</label>
						<select name="timezone" data-cerb-worker-selectmenu>
							{foreach from=$timezones item=timezone}
							<option value="{$timezone}" {if $worker->timezone==$timezone}selected="selected"{/if}>{$timezone}</option>
							{/foreach}
						</select>
					</div>

					<div class="cerb-ui-form--field" style="flex:1 1 calc(50% - 0.5em);min-width:13em;">
						<label class="cerb-ui-form--label">{'worker.time_format'|devblocks_translate}</label>
						<select name="time_format" data-cerb-worker-selectmenu>
							{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
							{foreach from=$timeformats item=timeformat}
								<option value="{$timeformat}" {if $worker->time_format==$timeformat}selected{/if}>{$smarty.now|devblocks_date:$timeformat}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>

	{* ─────────────── Availability ─────────────── *}
	<div id="{$form_id}Availability">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'preferences.account.availability.calendar_id'|devblocks_translate}</label>
					<div class="cerb-ui-record-chooser" id="calendarChooser_{$form_id}">
						{$avail_calendar = $calendars.{$worker->calendar_id}}
						{if $worker->calendar_id && $avail_calendar}
							<li data-context-id="{$avail_calendar->id}" data-label="{$avail_calendar->name}"></li>
						{/if}
					</div>
					<div class="cerb-ui-form--help">Leave empty and this worker is always unavailable.</div>
				</div>
			</div>
		</div>
	</div>
</div>

{if $worker->id}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="worker"}
{/if}

<div class="status"></div>

{if $active_worker->is_superuser}
<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($worker->id)}<button type="button" class="cerb-ui-button cerb-ui-button--subtle save-continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($worker->id) && !$is_self}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
{else}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert" style="margin-top:10px;">
		<div class="cerb-ui-header">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-ban cerb-ui-callout--icon"></span>
				<div class="cerb-ui-header--subtitle">{'error.core.no_acl.edit'|devblocks_translate}</div>
			</div>
		</div>
	</div>
{/if}
</form>

<style nonce="{DevblocksPlatform::getRequestNonce()}">
{literal}
/* Filter input lives outside a .cerb-ui-form, so style it (incl. icon-clearing left pad) here */
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
/* the .cerb-group-row display:flex rule beats UA [hidden]; re-assert so the filter can hide rows */
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
/* ── AI tab ──────────────────────────────────────────────────────────────── */

/* This tab's record choosers (filesystems, custom tools, interaction) draw their glyph with NO disc. A
   monogram needs a filled disc -- two letters have to be legible against a page -- but a glyph is already a
   shape and reads on its own, and every chooser here is a column of them. TWO selectors because a chooser is
   two places at once: the chips live in this tab, the dropdown mounts to document.body. */
.cerb-agent-config .cerb-ui-chooser--avatar-glyph,
.cerb-agent-config--chooser .cerb-ui-chooser--avatar-glyph {
	background-color: transparent;
	color: var(--cerb-color-background-contrast-100);
}

/* An embedded editor shell is inline-flex by default, so it collapses to its content inside a form field. */
.cerb-agent-config .cerb-ui-searchquery { width: 100%; }

/* Switched off, config kept. The panel stays readable -- this says "not running here", not "unavailable". */
.cerb-agent-panel--off .cerb-ui-form { opacity: 0.55; }


/* Icon-only compact role switcher (left column) */
.cerb-group-switcher { flex: 0 0 auto; }
.cerb-group-switcher button { padding: 0.25em 0.5em; min-height: 0; }
{/literal}
</style>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		// Tabs (no remember — always opens on the first tab)
		let tabsUl = document.getElementById('{$form_id}Tabs');
		let workerTabs = null;
		if(tabsUl && window.CerbUI && CerbUI.Tabs)
			workerTabs = new CerbUI.Tabs(tabsUl);

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Avatar chooser
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Record choosers (email / alternate emails / availability calendar)
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#emailChooser_{$form_id}')[0], {
				context: 'address',
				name: 'email_id',
				emptyIcon: 'mail',
				create: 'if-null',
				query: 'mailTransport.id:0 worker.id:0',
				searchPlaceholder: "{'common.email'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
			});

			new CerbUI.RecordChooser($popup.find('#altEmailChooser_{$form_id}')[0], {
				context: 'address',
				name: 'email_ids',
				multiple: true,
				emptyIcon: 'mail',
				create: true,
				query: 'mailTransport.id:0 worker.id:0',
				searchPlaceholder: "{'common.emails.alternate'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
			});

			new CerbUI.RecordChooser($popup.find('#calendarChooser_{$form_id}')[0], {
				context: 'calendar',
				name: 'calendar_id',
				emptyIcon: 'calendar',
				create: true,
				query: 'owner.worker:(id:{$worker->id})',
				searchPlaceholder: "{'common.calendar'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
			});
		}

		// Filterable select menus (localization)
		if(window.CerbUI && CerbUI.SelectMenu) {
			$frm.find('select[data-cerb-worker-selectmenu]').each(function() {
				new CerbUI.SelectMenu(this, { filter: true });
			});
		}

		// Aliases tag input (posts aliases[]; persisted CRLF-delimited)
		if(window.CerbUI && CerbUI.TagInput) {
			let aliasesEl = $popup.find('#aliasesInput_{$form_id}')[0];
			if(aliasesEl)
				new CerbUI.TagInput(aliasesEl, { placeholder: 'Add an alias and press Enter…' });
		}

		// Switchers — write the bound hidden input on select
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) {
						if(input) input.value = value;
					}
				});
			});
		}

		// Role — one three-way control over TWO columns. Inactive clears admin as well, so an
		// "inactive administrator" can't be expressed at all and reactivating means picking a role again.
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-worker-role]').each(function() {
				const disabledEl = document.getElementById(this.getAttribute('data-cerb-role-disabled'));
				const superuserEl = document.getElementById(this.getAttribute('data-cerb-role-superuser'));

				new CerbUI.Switcher(this, {
					value: this.getAttribute('data-cerb-role-value'),
					onSelect: function(value) {
						if(!disabledEl || !superuserEl) return;
						disabledEl.value = ('inactive' === value) ? '1' : '0';
						superuserEl.value = ('admin' === value) ? '1' : '0';
					}
				});
			});
		}

		// AI tab — an ordinary server-rendered form. What's left here is enhancement: the rail that picks a
		// panel, and the CerbUI component each field asked for. Every value posts as its own field, so nothing
		// in here owns state and nothing has to be kept in sync with the form.
		(function() {
			const aiHost = $popup.find('#{$form_id}Ai')[0];
			if(!aiHost) return;

			const rail = document.getElementById('agentRail_{$form_id}');
			const panels = aiHost.querySelectorAll('[data-cerb-agent-panel]');

			const showPanel = function(target) {
				panels.forEach(function(panel) {
					panel.hidden = (panel.getAttribute('data-cerb-agent-panel') !== target);
				});
			};

			if(rail && window.CerbUI && CerbUI.Sidebar) {
				// Returning truthy suppresses the component's own navigate-somewhere default action.
				const sidebar = new CerbUI.Sidebar(rail, {
					onSelect: function(li) { showPanel(li.getAttribute('data-target')); return true; }
				});

				const first = rail.querySelector('li[data-target]');
				if(first) sidebar.setActive(first);
			}

			// ── What Everywhere contributes, shown inside each surface's own field ────────────────────
			//
			// The additive fields (filesystems, tools, terminal) resolve to global ∪ surface, so a surface has
			// to show BOTH -- and a placeholder can't: it disappears the moment you add anything, which is
			// exactly when you most need to see what you're adding to. So the global set arrives as GHOST
			// tiles: same space as a real chip, no remove button, no value on submit.
			//
			// The two scalar fields keep a placeholder, because there is nothing to sit beside -- a blank one
			// IS the global value.
			//
			// Display only. Every field still posts its own value; nothing here owns state.
			const GLOBAL_SCOPE = '_global';
			const globalPanel = aiHost.querySelector('[data-cerb-agent-panel="' + GLOBAL_SCOPE + '"]');

			const fieldIn = function(panel, field) {
				return panel ? panel.querySelector('[data-cerb-agent-field="' + field + '"]') : null;
			};
			const controlIn = function(panel, field, selector) {
				const el = fieldIn(panel, field);
				return el ? el.querySelector(selector) : null;
			};
			const chooserIn = function(panel, field) {
				const host = controlIn(panel, field, '[data-cerb-agent-chooser]');
				return (host && window.CerbUI && CerbUI.RecordChooser) ? CerbUI.RecordChooser.from(host) : null;
			};
			const pickerIn = function(panel, field) {
				const host = controlIn(panel, field, '[data-cerb-agent-value-picker]');
				return (host && window.CerbUI && CerbUI.ValuePicker) ? CerbUI.ValuePicker.from(host) : null;
			};

			const surfacePanels = function() {
				return Array.prototype.filter.call(
					aiHost.querySelectorAll('[data-cerb-agent-panel]'),
					function(p) { return p.getAttribute('data-cerb-agent-panel') !== GLOBAL_SCOPE; }
				);
			};

			// A chooser's chips PLUS the hand-authored rows it can't represent -- the same set the merge will
			// actually apply, so a ghost never promises something the config doesn't do.
			const ghostsOf = function(field) {
				const owner = fieldIn(globalPanel, field);
				if(!owner) return [];

				const chooser = chooserIn(globalPanel, field);
				const chosen = chooser ? [].concat(chooser.getValue() || []).filter(Boolean) : [];

				// `value` is what makes a ghost EXCLUDE its record from being picked again here -- the same key
				// the chooser dedupes its own chips with.
				const ghosts = chosen.map(function(item) {
					return {
						value: item.id,
						context: item.context || '',
						label: String(item.label || ''),
						icon_name: item.icon_name || '',
						title: 'From Everywhere'
					};
				});

				// A hand-authored row resolved to no record, so it has no identity to exclude on -- and nothing
				// a search could return would collide with it anyway. Display only.
				owner.querySelectorAll('[data-cerb-agent-raw] input[type="hidden"]').forEach(function(input) {
					if(input.value) ghosts.push({ label: input.value, title: 'From Everywhere' });
				});

				return ghosts.filter(function(g) { return g.label; });
			};

			const syncGhosts = function() {
				if(!globalPanel) return;

				const sets = {
					mounts: ghostsOf('mounts'),
					tools: ghostsOf('tools'),
					automation: ghostsOf('automation')
				};

				surfacePanels().forEach(function(panel) {
					Object.keys(sets).forEach(function(field) {
						const chooser = chooserIn(panel, field);
						if(chooser) chooser.setGhosts(sets[field]);
					});
				});
			};

			const syncPlaceholders = function() {
				if(!globalPanel) return;

				['system_prompt', 'models_query'].forEach(function(field) {
					const source = controlIn(globalPanel, field, 'textarea');
					const value = source ? source.value.trim() : '';

					surfacePanels().forEach(function(panel) {
						const el = controlIn(panel, field, 'textarea');
						const owner = fieldIn(panel, field);
						if(el) el.placeholder = value || (owner ? owner.getAttribute('data-placeholder-empty') || '' : '');
					});
				});
			};

			// A granted namespace stops being pickable on every surface and becomes a ghost there. The option
			// itself never moves -- setGhosts() is the whole change, which is why nothing has to be rebuilt.
			const syncTerminal = function() {
				const granted = pickerIn(globalPanel, 'terminal');
				const values = granted ? granted.getValue() : [];

				surfacePanels().forEach(function(panel) {
					const picker = pickerIn(panel, 'terminal');
					if(picker) picker.setGhosts(values);
				});
			};

			const syncFromGlobal = function() {
				syncPlaceholders();
				syncGhosts();
				syncTerminal();
			};

			if(globalPanel) {
				globalPanel.querySelectorAll('[data-cerb-agent-field] textarea').forEach(function(el) {
					el.addEventListener('input', syncPlaceholders);
				});
			}


			// Filesystems, custom tools, interaction — one loop over what the template declared, so adding a
			// field is a template edit and nothing else.
			if(window.CerbUI && CerbUI.RecordChooser) {
				aiHost.querySelectorAll('[data-cerb-agent-chooser]').forEach(function(el) {
					const panel = el.closest('[data-cerb-agent-panel]');
					const isGlobal = !!panel && (panel.getAttribute('data-cerb-agent-panel') === GLOBAL_SCOPE);

					new CerbUI.RecordChooser(el, {
						context: el.getAttribute('data-context'),
						name: el.getAttribute('data-name'),
						multiple: el.hasAttribute('data-multiple'),
						emptyIcon: el.getAttribute('data-empty-icon') || '',
						// Every record here shares one identity (a volume, a tool, a chat), so a monogram of its
						// name says nothing. One glyph for all of them.
						itemIcon: el.getAttribute('data-item-icon') || '',
						query: el.getAttribute('data-query') || '',
						create: el.hasAttribute('data-create'),
						searchPlaceholder: el.getAttribute('data-placeholder') || '',
						// The dropdown mounts to document.body, so it has no ancestor inside this tab. Tagging
						// the popup is what lets a row and its chip be styled together and only here.
						panelClass: 'cerb-agent-config--chooser',
						// onChange, not onSelect: the latter is add-only by contract, and dropping a volume
						// from Everywhere has to stop every surface ghosting it.
						onChange: isGlobal ? function() { syncGhosts(); } : null
					});
				});
			}

			// Terminal namespaces are GRANTS, so they read as a set you add to -- not a row of checkboxes,
			// which would imply an unchecked one could take something away.
			if(window.CerbUI && CerbUI.ValuePicker) {
				aiHost.querySelectorAll('[data-cerb-agent-value-picker]').forEach(function(el) {
					const panel = el.closest('[data-cerb-agent-panel]');
					const owner = el.closest('[data-cerb-agent-field]');
					const isGlobal = !!panel && (panel.getAttribute('data-cerb-agent-panel') === GLOBAL_SCOPE);

					new CerbUI.ValuePicker(el, {
						multiple: true,
						searchPlaceholder: owner ? (owner.getAttribute('data-placeholder-empty') || '') : '',
						ghosts: (el.getAttribute('data-ghosts') || '').split(',').map(function(v) { return v.trim(); }).filter(Boolean),
						onSelect: isGlobal ? function() { syncTerminal(); } : null
					});
				});
			}

			// The same query vocabulary `llm.router: models_query:` autocompletes, so a query written in one
			// place reads the same in the other. The textarea keeps its name and posts its own value.
			if(window.CerbUI && CerbUI.SearchQuery) {
				const modelContext = 'cerb.contexts.agent.model';

				aiHost.querySelectorAll('[data-cerb-agent-models-query]').forEach(function(el) {
					new CerbUI.SearchQuery(el, {
						context: modelContext,
						onAutocomplete: CerbUI.SearchQuery.queryFieldSource(modelContext)
					});
				});
			}

			// "Runs here" — writes the hidden input that posts, dims the panel, and repaints the rail's pip.
			// Switching a surface OFF keeps its block: that's the whole reason the flag exists rather than
			// deleting the key, so the description, prompt, and tools authored here survive.
			if(window.CerbUI && CerbUI.Toggle) {
				aiHost.querySelectorAll('[data-cerb-agent-enabled]').forEach(function(cb) {
					const surface = cb.getAttribute('data-cerb-agent-enabled');
					const input = document.getElementById('agentEnabled_' + surface + '_{$form_id}');
					const panel = aiHost.querySelector('[data-cerb-agent-panel="' + surface + '"]');
					const pip = rail ? rail.querySelector('li[data-target="' + surface + '"] .cerb-ui-pip') : null;

					new CerbUI.Toggle(cb, {
						onChange: function(checked) {
							if(input) input.value = checked ? '1' : '0';
							if(panel) panel.classList.toggle('cerb-agent-panel--off', !checked);
							// Mirrors CerbUI.Sidebar._resolveColor() — the dot is currentColor.
							if(pip) pip.style.color = 'var(--cerb-color-tag-' + (checked ? 'green' : 'gray') + ')';
						}
					});
				});
			}



			// Seed every surface from the LIVE Everywhere scope now that its components exist, so the load state
			// and every later state come out of one derivation rather than agreeing by coincidence.
			syncFromGlobal();

			// A hand-authored entry is kept by its hidden input being posted, so removing the row removes it.
			aiHost.addEventListener('click', function(e) {
				const btn = e.target.closest('[data-cerb-agent-remove]');
				if(!btn) return;

				e.preventDefault();
				e.stopPropagation();

				const row = btn.closest('[data-cerb-agent-raw]');
				if(!row) return;

				const panel = row.closest('[data-cerb-agent-panel]');
				row.remove();

				if(panel && panel.getAttribute('data-cerb-agent-panel') === GLOBAL_SCOPE)
					syncGhosts();
			});
		})();

		// Type drives which tabs exist: an AI gets the AI tab and no Authentication (it can never hold a
		// session — see Page_Login::_routeAuthenticated). Live, so creating an agent needs no save first.
		(function() {
			const aiTab = $popup.find('[data-cerb-tab-ai]')[0];
			const loginTab = $popup.find('[data-cerb-tab-login]')[0];

			function applyType(isAi) {
				if(aiTab) aiTab.style.display = isAi ? '' : 'none';
				if(loginTab) loginTab.style.display = isAi ? 'none' : '';

				// Human-only profile fields (location, DOB) — meaningless on an agent
				$popup.find('[data-cerb-field-human]').each(function() { this.style.display = isAi ? 'none' : ''; });

				// Landing on a tab that just disappeared would strand its panel, so fall back to Profile
				const active = workerTabs ? workerTabs.activeTab : null;

				if(active && active.li && 'none' === active.li.style.display)
					workerTabs.select(0);
			}

			applyType({if $worker->is_ai}true{else}false{/if});

			$popup.find('.cerb-ui-switcher[data-cerb-worker-type] button').on('click', function() {
				applyType('1' === this.getAttribute('data-value'));
			});
		})();


		// Date of birth — YYYY-MM-DD in and out
		if(window.CerbUI && CerbUI.DatePicker)
			$popup.find('input[name=dob]').each(function() { new CerbUI.DatePicker(this, { outputFormat: 'YYYY-MM-DD' }); });

		// Groups roster — filterable full list; posts ONLY changed rows (delta) so 100+ groups stay cheap
		(function() {
			let panel = $popup.find('#{$form_id}GroupsPanel')[0];
			if(!panel) return;

			let list = panel.querySelector('[data-cerb-group-list]');
			let filterInput = panel.querySelector('[data-cerb-group-filter]');
			let countEl = panel.querySelector('[data-cerb-group-count]');
			let emptyEl = panel.querySelector('[data-cerb-group-empty]');

			// Live roster: every group + this worker's current role (built server-side from $groups + memberships)
			let groupCatalog = {$group_roster|json_encode nofilter};

			let roleMeta = {
				'1': { icon: 'user', label: "{'common.member'|devblocks_translate|capitalize|escape:'javascript' nofilter}" },
				'2': { icon: 'star', label: "{'common.manager'|devblocks_translate|capitalize|escape:'javascript' nofilter}" },
				'0': { icon: 'ban',  label: "{'common.neither'|devblocks_translate|capitalize|escape:'javascript' nofilter}" }
			};

			let buildRow = function(g) {
				let role = String(g.role || 0);
				let row = document.createElement('div');
				row.className = 'cerb-group-row';
				row.setAttribute('data-group-id', g.id);
				row.setAttribute('data-name', (g.name || '').toLowerCase());

				// Icon-only role switcher on the LEFT (tooltips convey member/manager/neither)
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
				input.name = 'group_memberships[' + g.id + ']';
				input.value = role;
				input.setAttribute('data-orig', role);
				input.disabled = true; // disabled inputs don't POST — enabled only once changed
				row.appendChild(input);

				let avatarHost = document.createElement('span');
				avatarHost.className = 'cerb-group-row--avatar cerb-u-ml-2' + (role === '0' ? ' cerb-u-opacity-25' : '');
				if(window.CerbUI && CerbUI.Avatar)
					avatarHost.appendChild(CerbUI.Avatar.create({ label: g.name || '?', seed: 'group:' + g.id, imageUrl: g.image || '', size: 22 }));
				row.appendChild(avatarHost);

				let name = document.createElement('a');
				name.className = 'cerb-peek-trigger cerb-u-fw-600 cerb-u-underline-hover cerb-group-row--name' + (role === '0' ? ' cerb-u-opacity-25' : '');
				name.setAttribute('data-context', 'group');
				name.setAttribute('data-context-id', g.id);
				name.textContent = g.name || ('#' + g.id);
				row.appendChild(name);

				return row;
			};

			let frag = document.createDocumentFragment();
			groupCatalog.forEach(function(g) { frag.appendChild(buildRow(g)); });
			list.appendChild(frag);
			if(window.jQuery) jQuery(list).find('.cerb-peek-trigger').cerbPeekTrigger();

			// Update a row: move the active segment, write the hidden input, and toggle its disabled (delta) state
			let setRowValue = function(row, val) {
				val = String(val);
				let input = row.querySelector('input[type=hidden]');
				if(!input) return;
				input.value = val;
				input.disabled = (val === input.getAttribute('data-orig')); // unchanged → won't POST
				row.querySelectorAll('[data-cerb-group-switcher] button').forEach(function(b) {
					b.classList.toggle('cerb-ui-switcher--active', b.getAttribute('data-value') === val);
				});
				// Dim the avatar + name when the worker is in neither role for this group
				let dim = (val === '0');
				['.cerb-group-row--avatar', '.cerb-group-row--name'].forEach(function(sel) {
					let el = row.querySelector(sel);
					if(el) el.classList.toggle('cerb-u-opacity-25', dim);
				});
			};

			// One delegated handler for every row (no per-row component instance)
			list.addEventListener('click', function(e) {
				let btn = e.target.closest('[data-cerb-group-switcher] button');
				if(!btn) return;
				e.stopPropagation();
				setRowValue(btn.closest('.cerb-group-row'), btn.getAttribute('data-value'));
			});

			let membersOnly = false;
			let rows = list.querySelectorAll('.cerb-group-row');

			// Visibility = text filter AND (members-only ? currently a member : true). Role edits do NOT
			// re-run this, so a row you just changed stays put; only the toggle + text input re-filter.
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

			// "Members-only" toggle (left of the filter) — collapse the list to current memberships
			let membersToggle = panel.querySelector('[data-cerb-group-members-only]');
			if(membersToggle && window.CerbUI && CerbUI.Toggle) {
				new CerbUI.Toggle(membersToggle.closest('.cerb-ui-toggle'), {
					onChange: function(checked) { membersOnly = checked; applyFilter(); }
				});
			}

			applyFilter();

			// "Set all" applies to the currently VISIBLE rows (filter → set all → tweak one)
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
	});
});
</script>
