{$peek_context = CerberusContexts::CONTEXT_GROUP}
{$peek_context_id = $group->id}
{$form_id = "formGroupsPeek{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="group">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($group) && !empty($group->id)}<input type="hidden" name="id" value="{$group->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:10px;">
	<tr>
		<td width="0%" nowrap="nowrap" valign="middle">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$group->name}" autocomplete="off" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">{'common.type'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<div>
				<label><input type="radio" name="is_private" value="0" {if !$group->is_private}checked="checked"{/if}> <b>{'common.public'|devblocks_translate|capitalize}</b> - group content is visible to non-members</label>
			</div>
			<div>
				<label><input type="radio" name="is_private" value="1" {if $group->is_private}checked="checked"{/if}> <b>{'common.private'|devblocks_translate|capitalize}</b> - group content is hidden from non-members</label>
			</div>
		</td>
	</tr>
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top">{'common.image'|devblocks_translate|capitalize}:</td>
		<td width="99%" valign="top">
			<div style="float:left;margin-right:5px;">
				<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=group&context_id={$group->id}{/devblocks_url}?v={$group->updated}" style="height:50px;width:50px;">
			</div>
			<div style="float:left;">
				<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}">{'common.edit'|devblocks_translate|capitalize}</button>
				<input type="hidden" name="avatar_image">
			</div>
		</td>
	</tr>
	
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" tbody=true bulk=false}
</table>

{$option_id = "divGroupCfgSubject{uniqid()}"}
<fieldset class="peek">
	<legend>Group-level mail settings: <small>(bucket defaults)</small></legend>

	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tr>
			<td valign="middle" width="0%" nowrap="nowrap">
				{'common.send.from'|devblocks_translate}: 
			</td>
			<td valign="middle" width="100%">
				<button type="button" class="chooser-abstract" data-field-name="reply_address_id" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-single="true" data-query="mailTransport.id:>0 isBanned:n isDefunct:n" data-query-required="" data-autocomplete="mailTransport.id:>0 isBanned:n isDefunct:n" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$replyto = DAO_Address::get($group->reply_address_id)}
				
				<ul class="bubbles chooser-container">
					{if $replyto}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=address&context_id={$replyto->id}{/devblocks_url}?v={$replyto->updated_at}"><input type="hidden" name="reply_address_id" value="{$replyto->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_ADDRESS}" data-context-id="{$replyto->id}">{$replyto->email}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
		
		<tr>
			<td valign="top" width="0%" nowrap="nowrap">
				{'common.send.as'|devblocks_translate}: 
			</td>
			<td valign="top">
				<textarea name="reply_personal" placeholder="e.g. Customer Support" class="cerb-template-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" style="width:100%;height:50px;">{$group->reply_personal}</textarea>
			</td>
		</tr>
		
		<tr>
			<td valign="middle" width="0%" nowrap="nowrap">
				{'common.signature'|devblocks_translate|capitalize}: 
			</td>
			<td valign="middle">
				<button type="button" class="chooser-abstract" data-field-name="reply_signature_id" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$signature = DAO_EmailSignature::get($group->reply_signature_id)}
				
				<ul class="bubbles chooser-container">
					{if $signature}
						<li><input type="hidden" name="reply_signature_id" value="{$signature->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_EMAIL_SIGNATURE}" data-context-id="{$signature->id}">{$signature->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>

		<tr>
			<td valign="middle" width="0%" nowrap="nowrap">
				{'common.encrypt.signing.key'|devblocks_translate|capitalize}:
			</td>
			<td valign="middle">
				<button type="button" class="chooser-abstract" data-field-name="reply_signing_key_id" data-context="{Context_GpgPrivateKey::ID}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>

				{$signing_key = DAO_GpgPrivateKey::get($group->reply_signing_key_id)}

				<ul class="bubbles chooser-container">
					{if $signing_key}
						<li><input type="hidden" name="reply_signing_key_id" value="{$signing_key->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{Context_GpgPrivateKey::ID}" data-context-id="{$signing_key->id}">{$signing_key->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>

		<tr>
			<td valign="middle" width="0%" nowrap="nowrap">
				HTML template: 
			</td>
			<td valign="middle">
				<button type="button" class="chooser-abstract" data-field-name="reply_html_template_id" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				{$html_template = DAO_MailHtmlTemplate::get($group->reply_html_template_id)}
				
				<ul class="bubbles chooser-container">
					{if $html_template}
						<li><input type="hidden" name="reply_html_template_id" value="{$html_template->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_MAIL_HTML_TEMPLATE}" data-context-id="{$html_template->id}">{$html_template->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
		
		<tr>
			<td valign="top" width="0%" nowrap="nowrap">
				Masks: 
			</td>
			<td valign="middle">
				<label><input type="checkbox" name="subject_has_mask" value="1" onclick="toggleDiv('{$option_id}',(this.checked)?'block':'none');" {if $group_settings.subject_has_mask}checked{/if}> Include ticket masks in message subjects:</label><br>
				<div id="{$option_id}" style="margin:5px 0;display:{if $group_settings.subject_has_mask}block{else}none{/if}">
					<b>Subject prefix:</b> (optional, e.g. "billing", "tech-support")<br>
					Re: [ <input type="text" name="subject_prefix" placeholder="prefix" value="{$group_settings.subject_prefix}" size="24"> #MASK-12345-678]: Subject<br>
				</div>
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek cerb-worker-group-memberships">
	<legend>{'common.members'|devblocks_translate|capitalize}</legend>
	
	<table style="text-align:center;border-spacing:0;">
		<thead>
			<tr>
				<th></th>
				<th width="60"><a href="javascript:;" data-value="1">{'common.member'|devblocks_translate|capitalize}</a></th>
				<th width="60"><a href="javascript:;" data-value="2">{'common.manager'|devblocks_translate|capitalize}</a></th>
				<th width="60"><a href="javascript:;" data-value="0">{'common.neither'|devblocks_translate|capitalize}</a></th>
			</tr>
		</thead>
		{foreach from=$workers item=worker key=worker_id name=workers}
		{$member = $members.$worker_id}
		<tbody style="{if 0 == $smarty.foreach.workers.iteration % 2}background-color:var(--cerb-color-background-contrast-240);{/if}">
			<tr>
				<td style="text-align:left;padding-right:30px;">
					<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}"><b>{$worker->getName()}</b></a>
				</td>
				<td>
					<input type="radio" name="group_memberships[{$worker->id}]" value="1" {if $member && !$member->is_manager}checked="checked"{/if}>
				</td>
				<td>
					<input type="radio" name="group_memberships[{$worker->id}]" value="2" {if $member && $member->is_manager}checked="checked"{/if}>
				</td>
				<td>
					<input type="radio" name="group_memberships[{$worker->id}]" value="0" {if !$member}checked="checked"{/if}>
				</td>
			</tr>
		</tbody>
		{/foreach}
	</table>
</fieldset>

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_GROUP context_id=$group->id}

{if !empty($group->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this group?
		
		{if !empty($destination_buckets)}
		<div style="color:var(--cerb-color-background-contrast-50);margin:10px;">
		
		<b>Move records from this group's buckets to:</b>
		
		<table cellpadding="2" cellspacing="0" border="0">
		
		{$buckets = $group->getBuckets()}
		{foreach from=$buckets item=bucket}
		<tr>
			<td>
				{$bucket->name}
			</td>
			<td>
				<span class="glyphicons glyphicons-right-arrow"></span> 
			</td>
			<td>
				<select name="move_deleted_buckets[{$bucket->id}]">
					{foreach from=$destination_buckets item=dest_buckets key=dest_group_id}
					{$dest_group = $groups.$dest_group_id}
						{foreach from=$dest_buckets item=dest_bucket}
						<option value="{$dest_bucket->id}">{$dest_group->name}: {$dest_bucket->name}</option>
						{/foreach}
					{/foreach}
				</select>
			</td> 
		</tr>
		{/foreach}
		
		</table>
		
		</div>
		{/if}
	</div>

	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($group->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.edit'|devblocks_translate|capitalize}: {'common.group'|devblocks_translate|capitalize}");
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);

		// Avatar
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		// Template builders
		
		$popup.find('textarea.cerb-template-trigger')
			.cerbTemplateTrigger()
		;
		
		$popup.find('button.chooser-abstract')
			.cerbChooserTrigger()
			;
		
		$popup.find('.cerb-peek-trigger')
			.cerbPeekTrigger()
			;
		
		// Group matrix
		
		var $group_fieldset = $popup.find('fieldset.cerb-worker-group-memberships');
		
		$group_fieldset.find('th a').on('click', function(e) {
			var $a = $(this);
			var value = $a.attr('data-value');
			var $table = $a.closest('table');
			
			$table.find('input:radio[value=' + value + ']').click();
		});
		
	});
});
</script>