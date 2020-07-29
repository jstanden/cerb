{$peek_context = CerberusContexts::CONTEXT_WORKER}
{$peek_context_id = $worker->id}
{$form_id = "frmWorkerEdit{uniqid()}"}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worker">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="id" value="{$worker->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div id="{$form_id}Tabs">
	<ul>
		<li><a href="#{$form_id}Profile">{'common.profile'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Groups">{'common.groups'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Login">{'common.authentication'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Localization">{'common.localization'|devblocks_translate|capitalize}</a></li>
		<li><a href="#{$form_id}Availability">{'common.availability'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="{$form_id}Profile">
		<table cellpadding="0" cellspacing="2" border="0" width="98%">
			<tr>
				<td width="0%" nowrap="nowrap">{'common.status'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					{if $active_worker->id == $worker->id}
						<input type="hidden" name="is_disabled" value="{$worker->is_disabled}">
						{if $worker->is_disabled}{'common.inactive'|devblocks_translate|capitalize}{else}{'common.active'|devblocks_translate|capitalize}{/if}
					{else}
						<label><input type="radio" name="is_disabled" value="0" {if !$worker->is_disabled}checked="checked"{/if}> {'common.active'|devblocks_translate|capitalize}</label>
						<label><input type="radio" name="is_disabled" value="1" {if $worker->is_disabled}checked="checked"{/if}> {'common.inactive'|devblocks_translate|capitalize}</label>
					{/if}
				</td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle"><b>{'common.name.first'|devblocks_translate|capitalize}:</b> </td>
				<td width="100%"><input type="text" name="first_name" value="{$worker->first_name}" style="width:98%;" autofocus="autofocus"></td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'common.name.last'|devblocks_translate|capitalize}: </td>
				<td width="100%"><input type="text" name="last_name" value="{$worker->last_name}" style="width:98%;"></td>
			</tr>
			<tr>
				<td width="1%" nowrap="nowrap" valign="top" title="(one per line)">
					{'common.aliases'|devblocks_translate|capitalize}:
				</td>
				<td width="99%" valign="top">
					<textarea name="aliases" cols="45" rows="3" style="width:98%;" placeholder="(one per line)">{implode("\n", $aliases)}</textarea>
				</td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap">{'common.privileges'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					{if $active_worker->id == $worker->id}
						<input type="hidden" name="is_superuser" value="{$worker->is_superuser}">
						{if !$worker->is_superuser}{'common.worker'|devblocks_translate|capitalize}{else}{'worker.is_superuser'|devblocks_translate|capitalize}{/if}
					{else}
						<label><input type="radio" name="is_superuser" value="0" {if !$worker->is_superuser}checked="checked"{/if}> {'common.worker'|devblocks_translate|capitalize}</label>
						<label><input type="radio" name="is_superuser" value="1" {if $worker->is_superuser}checked="checked"{/if}> {'worker.is_superuser'|devblocks_translate|capitalize}</label>
					{/if}
				</td>
			</tr>
			<tr>
				<td width="1%" nowrap="nowrap" valign="top">{'common.photo'|devblocks_translate|capitalize}:</td>
				<td width="99%" valign="top">
					<div style="float:left;margin-right:5px;">
						<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}" style="height:50px;width:50px;">
					</div>
					<div style="float:left;">
						<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{'common.edit'|devblocks_translate|capitalize}</button>
						<input type="hidden" name="avatar_image">
					</div>
				</td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'worker.title'|devblocks_translate|capitalize}: </td>
				<td width="100%"><input type="text" name="title" value="{$worker->title}" style="width:98%;"></td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle"><b>{'common.email'|devblocks_translate}</b>: </td>
				<td width="100%">
					<button type="button" class="chooser-abstract" data-field-name="email_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:0 worker.id:0 " data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{$addy = $worker->getEmailModel()}
						{if $addy}
						<li>
							<input type="hidden" name="email_id" value="{$addy->id}">
							<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}">
							<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}">{$addy->email}</a>
						</li>
						{/if}
					</ul>
				</td>
			</tr>
			
			<tr>
				<td width="0%" nowrap="nowrap" valign="top">{'common.emails.alternate'|devblocks_translate|capitalize}: </td>
				<td width="100%">
					<button type="button" class="chooser-abstract" data-field-name="email_ids[]" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-query="mailTransport.id:0 worker.id:0 " data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{$addys = $worker->getEmailModels()}
						{if is_array($addys)}
						{foreach from=$addys item=addy}
							{if $addy->id != $worker->email_id}
							<li>
								<input type="hidden" name="email_ids[]" value="{$addy->id}">
								<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}">
								<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}">{$addy->email}</a>
							</li>
							{/if}
						{/foreach}
						{/if}
					</ul>
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap">{'common.phone'|devblocks_translate|capitalize}:</td>
				<td width="99%">
					<input type="text" name="phone" value="{$worker->phone}" style="width:98%;" autocomplete="off" spellcheck="false">
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap">{'common.mobile'|devblocks_translate|capitalize}:</td>
				<td width="99%">
					<input type="text" name="mobile" value="{$worker->mobile}" style="width:98%;" autocomplete="off" spellcheck="false">
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap">{'common.location'|devblocks_translate|capitalize}:</td>
				<td width="99%">
					<input type="text" name="location" value="{$worker->location}" style="width:98%;" autocomplete="off" spellcheck="false">
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap" valign="top">{'common.gender'|devblocks_translate|capitalize}:</td>
				<td width="99%">
					<label><input type="radio" name="gender" value="M" {if $worker->gender == 'M'}checked="checked"{/if}> <span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);"></span> {'common.gender.male'|devblocks_translate|capitalize}</label>
					&nbsp; 
					&nbsp; 
					<label><input type="radio" name="gender" value="F" {if $worker->gender == 'F'}checked="checked"{/if}> <span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);"></span> {'common.gender.female'|devblocks_translate|capitalize}</label>
					&nbsp; 
					&nbsp; 
					<label><input type="radio" name="gender" value="" {if empty($worker->gender)}checked="checked"{/if}> {'common.unknown'|devblocks_translate|capitalize}</label>
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap">{'common.dob'|devblocks_translate|capitalize}:</td>
				<td width="99%">
					<input type="text" name="dob" value="{if $worker->dob}{$worker->dob}{/if}" style="width:98%;" autocomplete="off" spellcheck="false">
				</td>
			</tr>
			
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'worker.at_mention_name'|devblocks_translate}: </td>
				<td width="100%"><input type="text" name="at_mention_name" value="{$worker->at_mention_name}" style="width:98%;" placeholder="UserNickname"></td>
			</tr>
			
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" tbody=true bulk=false}
		</table>
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_WORKER context_id=$worker->id}
	</div>
	
	<div id="{$form_id}Localization">
		<table cellpadding="0" cellspacing="2" border="0" width="98%">
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'common.language'|devblocks_translate}: </td>
				<td width="100%">
					<select name="lang_code">
						{foreach from=$languages key=lang_code item=lang_name}
						<option value="{$lang_code}" {if $worker->language==$lang_code}selected="selected"{/if}>{$lang_name}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'common.timezone'|devblocks_translate}: </td>
				<td width="100%">
					<select name="timezone">
						{foreach from=$timezones item=timezone}
						<option value="{$timezone}" {if $worker->timezone==$timezone}selected="selected"{/if}>{$timezone}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td width="0%" nowrap="nowrap" valign="middle">{'worker.time_format'|devblocks_translate}: </td>
				<td width="100%">
					<select name="time_format">
						{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
						{foreach from=$timeformats item=timeformat}
							<option value="{$timeformat}" {if $worker->time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
						{/foreach}
					</select>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="{$form_id}Login">
		<fieldset class="peek black">
			<legend>{'common.password'|devblocks_translate|capitalize}</legend>
			
			<div>
				<label><input type="radio" name="is_password_disabled" value="0" {if !$worker->is_password_disabled}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="is_password_disabled" value="1" {if $worker->is_password_disabled}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize} (SSO only)</label>
			</div>
		</fieldset>
		
		<fieldset class="peek black">
			<legend>{'common.auth.mfa'|devblocks_translate|capitalize}</legend>
			
			<div>
				<label><input type="radio" name="is_mfa_required" value="1" {if $worker->is_mfa_required}checked="checked"{/if}> {'common.required'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="is_mfa_required" value="0" {if !$worker->is_mfa_required}checked="checked"{/if}> {'common.optional'|devblocks_translate|capitalize}</label>
			</div>
		</fieldset>

		<fieldset class="peek black">
			<legend>Timeout</legend>

			<div>
				Consider idle after <input type="text" name="timeout_idle_secs" value="{$worker->timeout_idle_secs}" maxlength="7" size="6"> seconds of inactivity.
			</div>
		</fieldset>
	</div>
	
	<div id="{$form_id}Groups">
		{if $worker->id}
		{$worker_groups = $worker->getMemberships()}
		{else}
		{$worker_groups = []}
		{/if}
		
		<table style="text-align:center;border-spacing:0;">
			<thead>
				<tr>
					<th></th>
					<th width="60"><a href="javascript:;" data-value="1">{'common.member'|devblocks_translate|capitalize}</a></th>
					<th width="60"><a href="javascript:;" data-value="2">{'common.manager'|devblocks_translate|capitalize}</a></th>
					<th width="60"><a href="javascript:;" data-value="0">{'common.neither'|devblocks_translate|capitalize}</a></th>
				</tr>
			</thead>
			{foreach from=$groups item=group key=group_id name=groups}
			{$member = $worker_groups.$group_id}
			<tbody style="{if 0 == $smarty.foreach.groups.iteration % 2}background-color:rgb(240,240,240);{/if}">
				<tr>
					<td style="text-align:left;padding-right:30px;">
						<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}"><b>{$group->name}</b></a>
					</td>
					<td>
						<input type="radio" name="group_memberships[{$group->id}]" value="1" {if $member && !$member->is_manager}checked="checked"{/if}>
					</td>
					<td>
						<input type="radio" name="group_memberships[{$group->id}]" value="2" {if $member && $member->is_manager}checked="checked"{/if}>
					</td>
					<td>
						<input type="radio" name="group_memberships[{$group->id}]" value="0" {if !$member}checked="checked"{/if}>
					</td>
				</tr>
			</tbody>
			{/foreach}
		</table>
	</div>
	
	<div id="{$form_id}Availability">
		<b>{'preferences.account.availability.calendar_id'|devblocks_translate}</b><br>
		
		<div style="margin-left:10px;">
			<select name="calendar_id">
				<option value="">- always unavailable -</option>
				{foreach from=$calendars item=calendar}
				<option value="{$calendar->id}" {if $calendar->id==$worker->calendar_id}selected="selected"{/if}>{$calendar->name}</option>
				{foreachelse}
				<option value="new" {if empty($worker->id)}selected="selected"{/if}>Create a new calendar</option>
				{/foreach}
			</select>
		</div>
	</div>
</div>

{if $worker->id}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this worker?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

{if $active_worker->is_superuser}
<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($worker->id) && $active_worker->is_superuser && $active_worker->id != $worker->id}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>	
{/if}

<br>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		{if $worker->id}
			{$popup_title = "{'common.edit'|devblocks_translate|capitalize}: {$worker->getName()}"}
		{else}
			{$popup_title = "{'common.create'|devblocks_translate|capitalize}: {'common.worker'|devblocks_translate|capitalize}"}
		{/if}
		$popup.dialog('option','title',"{$popup_title|escape:'javascript' nofilter}");
		
		// Tabs
		
		$('#{$form_id}Tabs').tabs();
		
		// Aliases
		
		var $aliases = $(this).find('textarea[name=aliases]').autosize();
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				// When the email changes on a new record, default the avatar chooser context
				if($(e.target).attr('data-field-name') == 'email_id') {
					var $chooser_email = $(e.target);
					var $bubble = $chooser_email.siblings('ul.chooser-container').find('> li:first input:hidden');
					
					if($bubble.length > 0) {
						var email_id = $bubble.val();
						if(email_id.length > 0) {
							$avatar_chooser.attr('data-create-defaults', 'email:' + email_id);
						}
					}
				}
			})
			;
		
		// Peeks
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Avatar chooser
		
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		// Group matrix
		
		var $group_fieldset = $popup.find('#{$form_id}Groups');
		
		$group_fieldset.find('th a').on('click', function(e) {
			var $a = $(this);
			var value = $a.attr('data-value');
			var $table = $a.closest('table');
			
			$table.find('input:radio[value=' + value + ']').click();
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>