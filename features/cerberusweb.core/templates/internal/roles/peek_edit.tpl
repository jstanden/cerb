{$peek_context = CerberusContexts::CONTEXT_ROLE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{if $model->privs_mode == 'all'}{$privs_mode_norm = 'all'}{elseif $model->privs_mode == 'itemized'}{$privs_mode_norm = 'itemized'}{else}{$privs_mode_norm = ''}{/if}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="role">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
			<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.image'|devblocks_translate|capitalize}</label>
			<div>
				<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
					data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_ROLE}" data-context-id="{$model->id}" data-name="avatar_image"
					data-avatar="{$model->name}" data-avatar-seed="role:{$model->id}"
					data-avatar-image="{devblocks_url}c=avatars&context=role&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}"></span>
				<input type="hidden" name="avatar_image" value="">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.membership'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-text-muted">Grant role <b>privileges</b> to these workers:</div>
			<div class="cerb-ui-searchquery" data-cerb-searchquery>
				<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
				<div class="cerb-ui-searchquery--field">
					<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
					<textarea name="member_query_worker" class="cerb-ui-searchquery--input" rows="1" placeholder="({'common.everyone'|devblocks_translate|lower})">{$model->member_query_worker}</textarea>
					<span class="cerb-ui-searchquery--caret-anchor"></span>
				</div>
				<div class="cerb-ui-searchquery--right">
					<a data-action="autocomplete" class="cerb-u-cursor-pointer cerb-u-text-muted" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.ownership'|devblocks_translate|capitalize}</label>
			<div class="cerb-u-text-muted">Records owned by this role can be <b>edited</b> by these workers:</div>
			<div class="cerb-ui-searchquery" data-cerb-searchquery>
				<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
				<div class="cerb-ui-searchquery--field">
					<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
					<textarea name="editor_query_worker" class="cerb-ui-searchquery--input" rows="1" placeholder="({'common.everyone'|devblocks_translate|lower})">{$model->editor_query_worker}</textarea>
					<span class="cerb-ui-searchquery--caret-anchor"></span>
				</div>
				<div class="cerb-ui-searchquery--right">
					<a data-action="autocomplete" class="cerb-u-cursor-pointer cerb-u-text-muted" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
				</div>
			</div>

			<div class="cerb-u-text-muted cerb-u-mt-2">Records owned by this role are <b>visible</b> to these workers:</div>
			<div class="cerb-ui-searchquery" data-cerb-searchquery>
				<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
				<div class="cerb-ui-searchquery--field">
					<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
					<textarea name="reader_query_worker" class="cerb-ui-searchquery--input" rows="1" placeholder="({'common.everyone'|devblocks_translate|lower})">{$model->reader_query_worker}</textarea>
					<span class="cerb-ui-searchquery--caret-anchor"></span>
				</div>
				<div class="cerb-ui-searchquery--right">
					<a data-action="autocomplete" class="cerb-u-cursor-pointer cerb-u-text-muted" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.privileges'|devblocks_translate|capitalize}</label>
			<input type="hidden" name="privs_mode" id="privsMode{$form_id}" value="{$privs_mode_norm}">
			<div>
				<div class="cerb-ui-switcher" data-cerb-input="privsMode{$form_id}">
					<button type="button" data-value="all" {if $privs_mode_norm == 'all'}class="cerb-ui-switcher--active"{/if}>{'common.all'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="" {if $privs_mode_norm == ''}class="cerb-ui-switcher--active"{/if}>{'common.none'|devblocks_translate|capitalize}</button>
					<button type="button" data-value="itemized" {if $privs_mode_norm == 'itemized'}class="cerb-ui-switcher--active"{/if}>Itemized</button>
				</div>
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{* Itemized ACL matrix — kept as the structured privilege grid (check-all headers + multi-column layout). *}
<div id="configAclItemized{$form_id}" {if $privs_mode_norm != 'itemized'}style="display:none;"{/if}>
	<div id="roleEditorPrivsOther" class="cerb-u-mb-3">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">{'common.actions'|devblocks_translate|capitalize}</div>
			<div class="cerb-ui-header--right">
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-check-all="roleEditorPrivsOther"><span class="cerb-icons cerb-icon-checked"></span> Check all</button>
			</div>
		</div>

		{foreach from=$core_acl item=section}
			{if empty($section.privs)}
			{else}
			{$container_id = uniqid()}
			<div class="cerb-ui-panel cerb-ui-panel--spaced" style="break-inside:avoid-column;page-break-inside:avoid;">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">
						<label data-cerb-check-all="privs{$container_id}" class="cerb-u-cursor-pointer">
						{$section.label}
						</label>
					</div>
				</div>

				<div id="privs{$container_id}">
					{foreach from=$section.privs item=priv key=priv_id}
						<label title="{$priv_id}"><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
					{/foreach}
				</div>
			</div>
			{/if}
		{/foreach}

		{if $core_acl.privs}
		<div class="cerb-u-mt-1 cerb-u-ml-1">
			{foreach from=$core_acl.privs item=priv key=priv_id}
				<label title="{$priv_id}"><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
			{/foreach}
		</div>
		{/if}

		{foreach from=$plugins_acl item=plugin key=plugin_id}
			{if empty($plugin.privs)}
			{else}
			<div class="cerb-ui-panel cerb-ui-panel--spaced" style="break-inside:avoid-column;page-break-inside:avoid;">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">
						<label data-cerb-check-all="privs{$plugin_id}" class="cerb-u-cursor-pointer">
						{$plugin.label}
						</label>
					</div>
				</div>

				<div id="privs{$plugin_id}">
					{foreach from=$plugin.privs item=priv key=priv_id}
						<label title="{$priv_id}"><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
					{/foreach}
				</div>
			</div>
			{/if}
		{/foreach}
	</div>

	<div id="roleEditorPrivsRecords">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">{'common.records'|devblocks_translate|capitalize}</div>
			<div class="cerb-ui-header--right">
				<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-check-all="roleEditorPrivsRecords"><span class="cerb-icons cerb-icon-checked"></span> Check all</button>
			</div>
		</div>

		{$priv_labels = []}
		{$priv_labels['broadcast'] = 'common.broadcast'|devblocks_translate|capitalize}
		{$priv_labels['comment'] = 'common.comment'|devblocks_translate|capitalize}
		{$priv_labels['create'] = 'common.create'|devblocks_translate|capitalize}
		{$priv_labels['delete'] = 'common.delete'|devblocks_translate|capitalize}
		{$priv_labels['export'] = 'common.export'|devblocks_translate|capitalize}
		{$priv_labels['import'] = 'common.import'|devblocks_translate|capitalize}
		{$priv_labels['merge'] = 'common.merge'|devblocks_translate|capitalize}
		{$priv_labels['update'] = 'common.update'|devblocks_translate|capitalize}
		{$priv_labels['update.bulk'] = 'common.update.bulk'|devblocks_translate|capitalize}
		{$priv_labels['watchers'] = 'common.watchers'|devblocks_translate|capitalize}

		<div style="column-count:3;column-width:300px;">
		{foreach from=$record_types item=record_type key=context_id}
			{$priv_prefix = "contexts.{$context_id}"}
			{$context = $contexts[$context_id]}
			{$available_privs = $context->params.acl[0]}

			{if $available_privs}
			<div class="cerb-ui-panel cerb-ui-panel--spaced" style="break-inside:avoid-column;page-break-inside:avoid;">
				<div class="cerb-ui-header cerb-ui-header--tight">
					<div class="cerb-ui-header--title-sm">
						<label data-cerb-check-all="contexts{$context_id}" class="cerb-u-cursor-pointer">
						{$record_type.label|capitalize}
						</label>
					</div>
				</div>

				<div id="contexts{$context_id}" style="column-width:140px;column-count:2;">
					{foreach from=$available_privs item=null key=priv}
					{$priv_id = "{$priv_prefix}.{$priv}"}
					<label title="{$priv_id}"><input type="checkbox" name="acl_privs[]" value="{$priv_prefix}.{$priv}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv_labels.$priv}</label><br>
					{/foreach}
				</div>
			</div>
			{/if}
		{/foreach}
		</div>
	</div>
</div>

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="role" detail="This also deletes all of its owned records."}
{/if}

<div class="buttons" style="margin-top:15px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" class="cerb-ui-button cerb-ui-button--subtle continue"><span class="cerb-icons cerb-icon-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.role'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Check all (mousedown preventDefault stops double-click text selection on the headers)
		$popup.find('[data-cerb-check-all]')
			.on('mousedown', function(e) {
				e.preventDefault();
			})
			.on('click', function(e) {
				e.stopPropagation();
				let selector = $(this).attr('data-cerb-check-all');
				checkAll(selector);
			});

		// This prevents the popup from being stranded downward by the height of the roles popup after submit
		const hide_tabs_on_submit = function(e) {
			if(!e.error) {
				$('#configAclItemized{$form_id}').hide();
			}
		};

		// Buttons
		$popup.find('button.save').click({ after: hide_tabs_on_submit }, Devblocks.callbackPeekEditSave);
		$popup.find('button.continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Privileges switcher — only the "itemized" mode reveals the ACL matrix
		let privsInput = document.getElementById('privsMode{$form_id}');
		let privsEl = $popup.find('[data-cerb-input="privsMode{$form_id}"]')[0];
		if(privsInput && privsEl && window.CerbUI && CerbUI.Switcher)
			new CerbUI.Switcher(privsEl, {
				value: privsInput.value,
				onSelect: function(value) {
					privsInput.value = value;
					$('#configAclItemized{$form_id}').toggle(value === 'itemized');
				}
			});

		// Avatar chooser
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Editors
		if(window.CerbUI && CerbUI.SearchQuery) {
			$popup.find('.cerb-ui-searchquery[data-cerb-searchquery]').each(function() {
				const sq = new CerbUI.SearchQuery(this, {
					onAutocomplete: CerbUI.SearchQuery.queryFieldSource('worker'),
					context: 'worker',
				});
				const acBtn = this.querySelector('[data-action=autocomplete]');
				if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
			});
		}
	});
});
</script>
