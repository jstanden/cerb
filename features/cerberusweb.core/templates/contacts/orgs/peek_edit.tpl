{$peek_context = CerberusContexts::CONTEXT_ORG}
{$peek_context_id = $org->id}
{$form_id = "peek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="org">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="id" value="{$org->id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $org instanceof Model_ContactOrg}
	{$addy = $org->getEmail()}
{/if}

<table cellpadding="0" cellspacing="2" border="0" width="98%" style="margin-bottom:10px;">
	<tr>
		<td width="0%" nowrap="nowrap">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%"><input type="text" name="org_name" value="{$org->name}" style="width:98%;"></td>
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
		<td valign="top">{'contact_org.street'|devblocks_translate|capitalize}: </td>
		<td><textarea name="street" style="width:98%;height:50px;">{$org->street}</textarea></td>
	</tr>
	<tr>
		<td>{'contact_org.city'|devblocks_translate|capitalize}: </td>
		<td><input type="text" name="city" value="{$org->city}" style="width:98%;"></td>
	</tr>
	<tr>
		<td>{'contact_org.province'|devblocks_translate|capitalize}.: </td>
		<td><input type="text" name="province" value="{$org->province}" style="width:98%;"></td>
	</tr>
	<tr>
		<td>{'contact_org.postal'|devblocks_translate|capitalize}: </td>
		<td><input type="text" name="postal" value="{$org->postal}" style="width:98%;"></td>
	</tr>
	<tr>
		<td>{'contact_org.country'|devblocks_translate|capitalize}: </td>
		<td>
			<input type="text" name="country" value="{$org->country}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap" valign="middle">{'common.email'|devblocks_translate|capitalize}:</td>
		<td width="99%" valign="top">
				<button type="button" class="chooser-abstract" data-field-name="email_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="org.id:{$org->id}" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{if $addy}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$addy->id}{/devblocks_url}?v={$addy->updated}"><input type="hidden" name="email_id" value="{$addy->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$addy->id}">{$addy->email}</a></li>
					{/if}
				</ul>
		</td>
	</tr>
	<tr>
		<td>{'common.phone'|devblocks_translate|capitalize}: </td>
		<td><input type="text" name="phone" value="{$org->phone}" style="width:98%;"></td>
	</tr>
	<tr>
		<td>{'common.website'|devblocks_translate|capitalize}: </td>
		<td><input type="text" name="website" value="{$org->website}" style="width:98%;" class="url"></td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">{'common.image'|devblocks_translate|capitalize}:</td>
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
	
	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ORG context_id=$org->id}

{include file="devblocks:cerberusweb.core::internal/cards/editors/comment.tpl"}

{if !empty($org->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this organization?
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	{if (!$org->id && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($org->id && $active_worker->hasPriv("contexts.{$peek_context}.update"))}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	{if $active_worker->hasPriv("contexts.{$peek_context}.delete") && !empty($org->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open',function(event,ui) {
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Title
		$popup.dialog('option','title', "{'common.edit'|devblocks_translate|capitalize|escape:'javascript' nofilter}: {'common.organization'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
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