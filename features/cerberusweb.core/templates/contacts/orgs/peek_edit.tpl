{$form_id = "peek{uniqid()}"}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="saveOrgPeek">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$org->id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.name'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="org_name" value="{$org->name}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right" valign="top">{'contact_org.street'|devblocks_translate|capitalize}: </td>
			<td><textarea name="street" style="width:98%;height:50px;">{$org->street}</textarea></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.city'|devblocks_translate|capitalize}: </td>
			<td><input type="text" name="city" value="{$org->city}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.province'|devblocks_translate|capitalize}.: </td>
			<td><input type="text" name="province" value="{$org->province}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.postal'|devblocks_translate|capitalize}: </td>
			<td><input type="text" name="postal" value="{$org->postal}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{'contact_org.country'|devblocks_translate|capitalize}: </td>
			<td>
				<input type="text" name="country" value="{$org->country}" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td align="right">{'common.phone'|devblocks_translate|capitalize}: </td>
			<td><input type="text" name="phone" value="{$org->phone}" style="width:98%;"></td>
		</tr>
		<tr>
			<td align="right">{if !empty($org->website)}<a href="{$org->website}" target="_blank">{'common.website'|devblocks_translate|capitalize}</a>{else}{'common.website'|devblocks_translate|capitalize}{/if}: </td>
			<td><input type="text" name="website" value="{$org->website}" style="width:98%;" class="url"></td>
		</tr>
		
		{* Watchers *}
		{if empty($org->id)}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.watchers'|devblocks_translate|capitalize}: </td>
			<td width="100%">
					<button type="button" class="chooser_watcher"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
			</td>
		</tr>
		{/if}
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top" align="right">{'common.image'|devblocks_translate|capitalize}:</td>
			<td width="99%" valign="top">
				<div style="float:left;margin-right:5px;">
					<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=org&context_id={$org->id}{/devblocks_url}?v={$org->updated}" style="height:50px;width:50px;">
				</div>
				<div style="float:left;">
					<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org->id}">{'common.edit'|devblocks_translate|capitalize}</button>
					<input type="hidden" name="avatar_image">
				</div>
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ORG context_id=$org->id}

{* Comments *}
{include file="devblocks:cerberusweb.core::internal/peek/peek_comments_pager.tpl" comments=$comments}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="2" cols="45" style="width:98%;" placeholder="{'comment.notify.at_mention'|devblocks_translate}"></textarea>
</fieldset>

{if !empty($org->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this organization?
	</div>
	
	<button type="button" class="delete"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	{if $active_worker->hasPriv('core.addybook.org.actions.update')}
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
		{if $active_worker->hasPriv('core.addybook.org.actions.delete') && !empty($org->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
	{else}
		<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
	{/if}
</div>

{if !empty($org->id)}
<div style="float:right;">
	<a href="{devblocks_url}&c=profiles&type=org&id={$org->id}-{$org->name|devblocks_permalink}{/devblocks_url}">{'addy_book.peek.view_full'|devblocks_translate}</a>
</div>
{/if}
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);

	$popup.one('popup_open',function(event,ui) {
		var $textarea = $(this).find('textarea[name=comment]');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Title
		$popup.dialog('option','title', "{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// @mentions
		
		var atwho_workers = {CerberusApplication::getAtMentionsWorkerDictionaryJson() nofilter};

		$textarea.atwho({
			at: '@',
			{literal}displayTpl: '<li>${name} <small style="margin-left:10px;">${title}</small> <small style="margin-left:10px;">@${at_mention}</small></li>',{/literal}
			{literal}insertTpl: '@${at_mention}',{/literal}
			data: atwho_workers,
			searchKey: '_index',
			limit: 10
		});
		
		// Worker autocomplete
		
		$popup.find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		// Country autocomplete
		ajax.countryAutoComplete($popup.find('form input[name=country]'));
		
		// Avatar
		
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		$popup.find(':input:text:first').focus();
	});
});
</script>