{$peek_context = CerberusContexts::CONTEXT_CONTACT}
{$peek_context_id = $model->id}
{$form_id = "frmContactPeekEdit{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="contact">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $model instanceof Model_Contact}
	{$org = $model->getOrg()}
	{$addy = $model->getEmail()}
{/if}

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name.first'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="first_name" value="{$model->first_name}" style="width:98%;" autocomplete="off" spellcheck="false" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name.last'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="last_name" value="{$model->last_name}" style="width:98%;" autocomplete="off" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top" title="(one per line)">
			<b>{'common.aliases'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%" valign="top">
			<textarea name="aliases" cols="45" rows="3" style="width:98%;" placeholder="(one per line)">{$aliases|implode:"\n"}</textarea>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.title'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="title" value="{$model->title}" style="width:98%;" autocomplete="off" spellcheck="true">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle"><b>{'common.organization'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
				<button type="button" class="chooser-abstract" data-field-name="org_id" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{if $org}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}"><input type="hidden" name="org_id" value="{$org->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}">{$org->name}</a></li>
					{/if}
				</ul>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle"><b>{'common.email'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
				<button type="button" class="chooser-abstract" data-field-name="primary_email_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="org.id:{$org->id}" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{if $addy}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}"><input type="hidden" name="primary_email_id" value="{$addy->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}">{$addy->email}</a></li>
					{/if}
				</ul>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.location'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="location" value="{$model->location}" style="width:98%;" autocomplete="off" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle"><b>{'common.language'|devblocks_translate}:</b></td>
		<td width="100%">
			<select name="language">
				<option value=""></option>
				{foreach from=$languages key=lang_code item=lang_name}
				<option value="{$lang_code}" {if $model->language==$lang_code}selected="selected"{/if}>{$lang_name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle"><b>{'common.timezone'|devblocks_translate}:</b></td>
		<td width="100%">
			<select name="timezone">
				<option value=""></option>
				{foreach from=$timezones item=timezone}
				<option value="{$timezone}" {if $model->timezone==$timezone}selected="selected"{/if}>{$timezone}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.phone'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="phone" value="{$model->phone}" style="width:98%;" autocomplete="off" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.mobile'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="mobile" value="{$model->mobile}" style="width:98%;" autocomplete="off" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.gender'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<label><input type="radio" name="gender" value="M" {if $model->gender == 'M'}checked="checked"{/if}> <span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);"></span> {'common.gender.male'|devblocks_translate|capitalize}</label>
			&nbsp; 
			&nbsp; 
			<label><input type="radio" name="gender" value="F" {if $model->gender == 'F'}checked="checked"{/if}> <span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);"></span> {'common.gender.female'|devblocks_translate|capitalize}</label>
			&nbsp; 
			&nbsp; 
			<label><input type="radio" name="gender" value="" {if empty($model->gender)}checked="checked"{/if}> {'common.unknown'|devblocks_translate|capitalize}</label>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.dob'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="dob" value="{if $model->dob}{$model->dob}{/if}" style="width:98%;" autocomplete="off" spellcheck="false">
		</td>
	</tr>
	
	{if empty($model->id)}
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{$translate->_('common.watchers')|capitalize}: </td>
		<td width="100%">
			<button type="button" class="chooser_watcher"><span class="glyphicons glyphicons-search"></span></button>
			<ul class="chooser-container bubbles" style="display:block;"></ul>
		</td>
	</tr>
	{/if}
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.photo'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
			<div style="float:left;margin-right:5px;">
				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=contact&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="height:50px;width:50px;">
			</div>
			<div style="float:left;">
				<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$model->id}" data-create-defaults="{if $addy}email:{$addy->id}{/if} {if $org}org:{$org->id}{/if}">{'common.edit'|devblocks_translate|capitalize}</button>
				<input type="hidden" name="avatar_image">
			</div>
		</td>
	</tr>
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>
	
<fieldset class="peek">
	<legend>Authentication</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.username'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="username" value="{$model->username}" style="width:98%;" autocomplete="off" spellcheck="false">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.password'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<input type="text" name="password" value="" style="width:98%;" autocomplete="off" spellcheck="false" placeholder="(leave blank to keep current password)">
			</td>
		</tr>
	</table>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this contact?
	</div>
	
	<button type="button" class="delete"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	var $chooser_org = $popup.find('button.chooser-abstract[data-field-name="org_id"]');
	var $chooser_email = $popup.find('button.chooser-abstract[data-field-name="primary_email_id"]');
	var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
	var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.contact'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		var $aliases = $(this).find('textarea[name=aliases]').autosize();
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
			
		{if empty($model->id)}
		// Watchers

		$popup.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		{/if}
		
		// Abstract choosers
		
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				// When the org changes, default the contact chooser filter
				if($(e.target).attr('data-field-name') == 'org_id') {
					var $bubble = $chooser_org.siblings('ul.chooser-container').find('> li:first input:hidden');
					
					if($bubble.length > 0) {
						var org_id = $bubble.val();
						$chooser_email.attr('data-query', 'org.id:' + org_id);
					}
				} else if($(e.target).attr('data-field-name') == 'primary_email_id') {
					var $bubble = $chooser_email.siblings('ul.chooser-container').find('> li:first input:hidden');
					
					if($bubble.length > 0) {
						var email_id = $bubble.val();
						if(email_id.length > 0) {
							$avatar_chooser.attr('data-context','{CerberusContexts::CONTEXT_ADDRESS}');
							$avatar_chooser.attr('data-context-id',email_id);
						}
					}
				}
			})
			;
		
		// Peeks
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Avatar
		
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>