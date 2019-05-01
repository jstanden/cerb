{* [TODO] Email + Phone verified *}

{$peek_context = CerberusContexts::CONTEXT_IDENTITY}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="identity">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $model instanceof Model_Identity}
	{$addy = $model->getEmailModel()}
	{$identity_pool = $model->getIdentityPool()}
{/if}

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle"><b>{'common.identity.pool'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
			<button type="button" class="chooser-abstract" data-field-name="pool_id" data-context="{CerberusContexts::CONTEXT_IDENTITY_POOL}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $identity_pool}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=identity_pool&context_id={$identity_pool->id}{/devblocks_url}?v={$identity_pool->updated_at}"><input type="hidden" name="pool_id" value="{$identity_pool->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_IDENTITY_POOL}" data-context-id="{$identity_pool->id}">{$identity_pool->name}</a></li>
				{/if}
			</ul>
		</td>
	</tr>

	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.identity.given_name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="given_name" value="{$model->given_name}"  style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.identity.middle_name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="middle_name" value="{$model->middle_name}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.identity.family_name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="family_name" value="{$model->family_name}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'dao.identity.nickname'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="nickname" value="{$model->nickname}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.username'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="username" value="{$model->username}" style="width:98%;">
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
		<td width="1%" nowrap="nowrap" valign="middle"><b>{'common.email'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
			<button type="button" class="chooser-abstract" data-field-name="email_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $addy}
					<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}"><input type="hidden" name="email_id" value="{$addy->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}">{$addy->email}</a></li>
				{/if}
			</ul>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.phone'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="phone_number" value="{$model->phone_number}" style="width:98%;" autocomplete="off" spellcheck="false">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.dob'|devblocks_translate|capitalize|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="birthdate" value="{if $model->birthdate}{$model->birthdate}{/if}" style="width:98%;" autocomplete="off" spellcheck="false" placeholder="YYYY-MM-DD">
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle"><b>{'common.language'|devblocks_translate|capitalize}:</b></td>
		<td width="100%">
			<select name="locale">
				<option value=""></option>
				{foreach from=$languages key=lang_code item=lang_name}
				<option value="{$lang_code}" {if $model->locale==$lang_code}selected="selected"{/if}>{$lang_name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle"><b>{'common.timezone'|devblocks_translate|capitalize}:</b></td>
		<td width="100%">
			<select name="zoneinfo">
				<option value=""></option>
				{foreach from=$timezones item=timezone}
				<option value="{$timezone}" {if $model->zoneinfo==$timezone}selected="selected"{/if}>{$timezone}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.website'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="website" value="{$model->website}" style="width:98%;">
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.photo'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
			<div style="float:left;margin-right:5px;">
				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=identity&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="height:50px;width:50px;">
			</div>
			<div style="float:left;">
				<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_IDENTITY}" data-context-id="{$model->id}" data-create-defaults="{if $addy}email:{$addy->id}{/if}">{'common.edit'|devblocks_translate|capitalize}</button>
				<input type="hidden" name="avatar_image">
			</div>
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
		Are you sure you want to permanently delete this identity?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
	var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.identity'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				if($(e.target).attr('data-field-name') == 'email_id') {
					var $bubble = $chooser_email.siblings('ul.chooser-container').find('> li:first input:hidden');
					
					if($bubble.length > 0) {
						var email_id = $bubble.val();
						if(email_id.length > 0) {
							// [TODO]
							//$avatar_chooser.attr('data-context','{CerberusContexts::CONTEXT_ADDRESS}');
							//$avatar_chooser.attr('data-context-id',email_id);
						}
					}
				}
			})
			;
		
		// Peeks
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Avatar
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
	});
});
</script>
