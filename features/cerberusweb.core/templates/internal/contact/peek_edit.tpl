{$peek_context = CerberusContexts::CONTEXT_CONTACT}
{$peek_context_id = $model->id}
{$form_id = "frmContactPeekEdit{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="contact">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $model instanceof Model_Contact}
	{$org = $model->getOrg()}
	{$addy = $model->getEmail()}
{/if}

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name.first'|devblocks_translate|capitalize}</label>
				<input type="text" name="first_name" value="{$model->first_name}" autocomplete="off" spellcheck="false" autofocus="autofocus">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.name.last'|devblocks_translate|capitalize}</label>
				<input type="text" name="last_name" value="{$model->last_name}" autocomplete="off" spellcheck="false">
			</div>
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
			<label class="cerb-ui-form--label">{'common.title'|devblocks_translate|capitalize}</label>
			<input type="text" name="title" value="{$model->title}" autocomplete="off">
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
				<label class="cerb-ui-form--label">{'common.email'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="emailChooser_{$form_id}">
					{if $addy}
						<li data-context-id="{$addy->id}" data-label="{$addy->email}" data-image="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}"></li>
					{/if}
				</div>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.location'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-location"></span>
					<input type="text" name="location" value="{$model->location}" autocomplete="off" spellcheck="false">
				</label>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.dob'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-calendar"></span>
					<input type="text" name="dob" value="{if $model->dob}{$model->dob}{/if}" autocomplete="off" spellcheck="false" placeholder="YYYY-MM-DD">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.language'|devblocks_translate|capitalize}</label>
				<select name="language" data-cerb-contact-selectmenu>
					<option value="">({'common.none'|devblocks_translate|lower})</option>
					{foreach from=$languages key=lang_code item=lang_name}
					<option value="{$lang_code}" {if $model->language==$lang_code}selected="selected"{/if}>{$lang_name}</option>
					{/foreach}
				</select>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.timezone'|devblocks_translate|capitalize}</label>
				<select name="timezone" data-cerb-contact-selectmenu>
					<option value="">({'common.none'|devblocks_translate|lower})</option>
					{foreach from=$timezones item=timezone}
					<option value="{$timezone}" {if $model->timezone==$timezone}selected="selected"{/if}>{$timezone}</option>
					{/foreach}
				</select>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.phone'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-phone-handset"></span>
					<input type="text" name="phone" value="{$model->phone}" autocomplete="off" spellcheck="false">
				</label>
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.mobile'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mobile"></span>
					<input type="text" name="mobile" value="{$model->mobile}" autocomplete="off" spellcheck="false">
				</label>
			</div>
		</div>

		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.gender'|devblocks_translate|capitalize}</label>
				<div>
					<input type="hidden" name="gender" id="gender_{$form_id}" value="{$model->gender}">
					<div class="cerb-ui-switcher" data-cerb-input="gender_{$form_id}">
						<button type="button" data-value="M"{if $model->gender == 'M'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-gender-male"></span> {'common.gender.pronouns.male'|devblocks_translate}</button>
						<button type="button" data-value="F"{if $model->gender == 'F'} class="cerb-ui-switcher--active"{/if}><span class="cerb-icons cerb-icon-gender-female"></span> {'common.gender.pronouns.female'|devblocks_translate}</button>
						<button type="button" data-value=""{if empty($model->gender)} class="cerb-ui-switcher--active"{/if}>{'common.gender.pronouns.neutral'|devblocks_translate}</button>
					</div>
				</div>
			</div>

			{if empty($model->id)}
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.watchers'|devblocks_translate|capitalize}</label>
				<div class="cerb-ui-record-chooser" id="watchersChooser_{$form_id}"></div>
			</div>
			{/if}
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.photo'|devblocks_translate|capitalize}</label>
			<div>
				<span class="cerb-ui-avatar" style="width:50px;height:50px;font-size:21px;"
					data-cerb-image-editor data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$model->id}" data-name="avatar_image"
					data-avatar="{$model->getName()}" data-avatar-seed="contact:{$model->id}"
					data-avatar-image="{devblocks_url}c=avatars&context=contact&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}"></span>
				<input type="hidden" name="avatar_image" value="">
			</div>
		</div>

		{if !empty($custom_fields)}
		{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
		{/if}
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">{'common.authentication'|devblocks_translate|capitalize}</div>
	</div>
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--row">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.username'|devblocks_translate|capitalize}</label>
				<input type="text" name="username" value="{$model->username}" autocomplete="off" spellcheck="false">
			</div>
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">{'common.password'|devblocks_translate|capitalize}</label>
				<label class="cerb-ui-form--control">
					<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-key"></span>
					<input type="text" name="password" value="" autocomplete="off" spellcheck="false" placeholder="(leave blank to keep current password)">
				</label>
			</div>
		</div>
	</div>
</div>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
	{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="contact"}
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.contact'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// Avatar
		if(window.CerbUI && CerbUI.ImageEditor)
			$popup.find('[data-cerb-image-editor]').each(function() { new CerbUI.ImageEditor(this); });

		// Aliases tag input (posts aliases[]; persisted CRLF-delimited)
		if(window.CerbUI && CerbUI.TagInput) {
			let aliasesEl = $popup.find('#aliasesInput_{$form_id}')[0];
			if(aliasesEl)
				new CerbUI.TagInput(aliasesEl, { placeholder: 'Add an alias and press Enter…' });
		}

		// Select menus
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('select[data-cerb-contact-selectmenu]').each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

		// Switcher
		if(window.CerbUI && CerbUI.Switcher) {
			$popup.find('.cerb-ui-switcher[data-cerb-input]').each(function() {
				let input = document.getElementById(this.getAttribute('data-cerb-input'));
				new CerbUI.Switcher(this, {
					value: input ? input.value : null,
					onSelect: function(value) { if(input) input.value = value; }
				});
			});
		}

		// Date of birth — YYYY-MM-DD in and out
		if(window.CerbUI && CerbUI.DatePicker)
			$popup.find('input[name=dob]').each(function() { new CerbUI.DatePicker(this, { outputFormat: 'YYYY-MM-DD' }); });

		// Record choosers
		let emailChooser = null;
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('#orgChooser_{$form_id}')[0], {
				context: 'org',
				name: 'org_id',
				emptyIcon: 'building-office',
				create: 'if-null',
				searchPlaceholder: "{'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}",
				onSelect: function(item) {
					if(emailChooser && item && item.id) emailChooser.setQuery('org.id:' + item.id);
				}
			});

			emailChooser = new CerbUI.RecordChooser($popup.find('#emailChooser_{$form_id}')[0], {
				context: 'address',
				name: 'primary_email_id',
				emptyIcon: 'mail',
				create: 'if-null',
				{if $org}query: 'org.id:{$org->id}',{/if}
				searchPlaceholder: "{'common.email'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
			});

			let $watchersEl = $popup.find('#watchersChooser_{$form_id}');
			if($watchersEl.length) {
				new CerbUI.RecordChooser($watchersEl[0], {
					context: 'worker',
					name: 'add_watcher_ids',
					multiple: true,
					emptyIcon: 'user',
					query: 'isDisabled:n',
					searchPlaceholder: "{'common.watchers'|devblocks_translate|capitalize|escape:'javascript' nofilter}"
				});
			}
		}
	});
});
</script>
