{$form_id = "formGroupsPeek{uniqid()}"}
{$peek_context = CerberusContexts::CONTEXT_GROUP}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="group">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($group) && !empty($group->id)}<input type="hidden" name="id" value="{$group->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="98%">

	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="middle">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" style="width:98%;border:1px solid rgb(180,180,180);padding:2px;" value="{$group->name}" autocomplete="off" autofocus="autofocus">
		</td>
	</tr>
	
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{'common.type'|devblocks_translate|capitalize}: </td>
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
		<td width="1%" nowrap="nowrap" valign="top" align="right">{'common.image'|devblocks_translate|capitalize}:</td>
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
	
</table>

{$tabs_id = "groupPeekTabs{uniqid()}"}
<div id="{$tabs_id}" style="margin:5px 0px 15px 0px;">

<ul>
	<li><a href="#{$tabs_id}Members">{'common.members'|devblocks_translate|capitalize}</a></li>
	<li><a href="#{$tabs_id}Options">{'common.options'|devblocks_translate|capitalize}</a></li>
</ul>

{$option_id = "divGroupCfgSubject{uniqid()}"}
<div id="{$tabs_id}Options">
	<label><input type="checkbox" name="subject_has_mask" value="1" onclick="toggleDiv('{$option_id}',(this.checked)?'block':'none');" {if $group_settings.subject_has_mask}checked{/if}> Include custom prefix and mask in message subject:</label><br>
	<blockquote id="{$option_id}" style="margin-left:20px;margin-bottom:0px;display:{if $group_settings.subject_has_mask}block{else}none{/if}">
		<b>Subject prefix:</b> (optional, e.g. "Billing", "Tech Support")<br>
		Re: [ <input type="text" name="subject_prefix" placeholder="prefix" value="{$group_settings.subject_prefix}" size="24"> #MASK-12345-678]: This is the subject line<br>
	</blockquote>
</div>

<div id="{$tabs_id}Members" style="max-height: 250px;overflow:auto;">
{foreach from=$workers item=worker}
<div>
	<input type="hidden" name="member_ids[]" value="{$worker->id}">
	<select name="member_levels[]">
		<option value=""></option>
		<option value="1" {if isset($members.{$worker->id}) && !$members.{$worker->id}->is_manager}selected="selected"{/if}>{'common.member'|devblocks_translate|capitalize}</option>
		<option value="2" style="font-weight:bold;" {if isset($members.{$worker->id}) && $members.{$worker->id}->is_manager}selected="selected"{/if}>{'common.manager'|devblocks_translate|capitalize}</option>
	</select>
	 &nbsp; 
	 {$worker->getName()} {if !empty($worker->title)}<span style="color:rgb(0,120,0);">({$worker->title})</span>{/if}
</div>
{/foreach}
</div>

</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_GROUP context_id=$group->id}

{if !empty($group->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this group?
		
		{if !empty($destination_buckets)}
		<div style="color:rgb(50,50,50);margin:10px;">
		
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
	
	<button type="button" class="delete"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> Confirm</button>
	{if $active_worker->is_superuser}<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>{/if}
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($group->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($group->id)}
<div style="float:right;">
	<a href="{devblocks_url}&c=profiles&type=group&id={$group->id}{/devblocks_url}-{$group->name|devblocks_permalink}">{'addy_book.peek.view_full'|devblocks_translate}</a>
</div>
{/if}

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

		// Tabs
		$('#{$tabs_id}').tabs({ });
	});
});
</script>