{$peek_context = CerberusContexts::CONTEXT_CUSTOM_RECORD}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="custom_record">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="custom record"}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.singular'|devblocks_translate|capitalize}</label>
				<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.plural'|devblocks_translate|capitalize}</label>
				<input type="text" name="name_plural" value="{$model->name_plural}">
			</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.uri'|devblocks_translate}</label>
			<input type="text" name="uri" value="{$model->uri}" spellcheck="false" placeholder="Letters, numbers, and underscores (e.g. my_record)">
			<div class="cerb-ui-form--help">The alias for this record used in profile URLs, etc. Must be lowercase and only contain letters, numbers, and underscores.</div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.icon'|devblocks_translate|capitalize}</label>
			<div>
				<input type="text" name="params[icon]" value="{if !empty($model->params.icon)}{$model->params.icon}{else}collection{/if}">
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Ownable by</div>
	</div>

	{$owner_contexts = $model->params.owners.contexts}
	{if !is_array($owner_contexts)}{$owner_contexts = []}{/if}

	<div class="cerb-ui-form">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="ownerApp{$form_id}" name="params[owners][contexts][]" value="cerberusweb.contexts.app" {if in_array('cerberusweb.contexts.app', $owner_contexts)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="ownerApp{$form_id}">Cerb</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="ownerGroup{$form_id}" name="params[owners][contexts][]" value="cerberusweb.contexts.group" {if in_array('cerberusweb.contexts.group', $owner_contexts)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="ownerGroup{$form_id}">{'common.groups'|devblocks_translate|capitalize}</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="ownerRole{$form_id}" name="params[owners][contexts][]" value="cerberusweb.contexts.role" {if in_array('cerberusweb.contexts.role', $owner_contexts)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="ownerRole{$form_id}">{'common.roles'|devblocks_translate|capitalize}</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="ownerWorker{$form_id}" name="params[owners][contexts][]" value="cerberusweb.contexts.worker" {if in_array('cerberusweb.contexts.worker', $owner_contexts)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="ownerWorker{$form_id}">{'common.workers'|devblocks_translate|capitalize}</label>
		</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.options'|devblocks_translate|capitalize}</div>
	</div>

	{$options = $model->params.options}
	{if !is_array($options)}{$options = []}{/if}

	<div class="cerb-ui-form">
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="optHideSearch{$form_id}" name="params[options][]" value="hide_search" {if in_array('hide_search', $options)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="optHideSearch{$form_id}">Hide in search menu</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="optAvatars{$form_id}" name="params[options][]" value="avatars" {if in_array('avatars', $options)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="optAvatars{$form_id}">Profile images</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="optAttachments{$form_id}" name="params[options][]" value="attachments" {if in_array('attachments', $options)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="optAttachments{$form_id}">{'common.attachments'|devblocks_translate|capitalize}</label>
		</div>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
			<label class="cerb-ui-toggle">
				<input type="checkbox" id="optComments{$form_id}" name="params[options][]" value="comments" {if in_array('comments', $options)}checked{/if}>
				<span class="cerb-ui-toggle--slider"></span>
			</label>
			<label for="optComments{$form_id}">{'common.comments'|devblocks_translate|capitalize}</label>
		</div>
	</div>
</div>

{if !$model->id && $roles}
<div data-cerb-fieldset="privileges" class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.privileges'|devblocks_translate|capitalize}</div>
	</div>

	{$priv_labels = []}
	{$priv_labels['comment'] = 'common.comment'|devblocks_translate|capitalize}
	{$priv_labels['create'] = 'common.create'|devblocks_translate|capitalize}
	{$priv_labels['delete'] = 'common.delete'|devblocks_translate|capitalize}
	{$priv_labels['export'] = 'common.export'|devblocks_translate|capitalize}
	{$priv_labels['import'] = 'common.import'|devblocks_translate|capitalize}
	{$priv_labels['update'] = 'common.update'|devblocks_translate|capitalize}

	<div class="cerb-ui-form">
		{foreach from=$roles item=role key=role_id}
		<div class="cerb-ui-panel">
			<div class="cerb-ui-header cerb-ui-header--tight">
				<div class="cerb-ui-header--title-sm">
					<label data-cerb-role-id="{$role_id}">
					{$role->name}
					</label>
				</div>
			</div>

			<div id="role{$role_id}" style="column-count:2;column-width:50%;">
				{foreach from=$priv_labels item=priv_label key=priv}
				<label><input type="checkbox" name="role_privs[{$role->id}][]" value="{$priv}"> {$priv_label}</label><br>
				{/foreach}
			</div>
		</div>
		{/foreach}
	</div>
</div>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="custom record"}
{/if}

<div class="buttons" style="margin-top:15px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.custom_record'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		$popup.find('[autofocus]:first').focus();

		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('a.cerb-peek-trigger').cerbPeekTrigger();

		// Icon picker
		if(window.CerbUI && CerbUI.IconPicker)
			$popup.find('input[name="params[icon]"]').each(function() { new CerbUI.IconPicker(this, { emptyIcon:'collection' }); });

		// Fieldsets

		$frm.find('[data-cerb-fieldset=privileges] [data-cerb-role-id]')
			.css('cursor', 'pointer')
			.on('click', function(e) {
				e.stopPropagation();
				let role_id = $(this).attr('data-cerb-role-id');
				checkAll('role' + role_id);
			})
		;
	});
});
</script>
