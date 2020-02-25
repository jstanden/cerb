{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invokeTab">
<input type="hidden" name="tab_id" value="{$tab->id}">
<input type="hidden" name="section" value="worker">
<input type="hidden" name="action" value="saveSettingsSectionTabJson">
<input type="hidden" name="worker_id" value="{$worker->id}">
<input type="hidden" name="tab" value="profile">

<fieldset class="peek">
	<legend>{'preferences.account.settings'|devblocks_translate|capitalize}</legend>
	
	<div style="margin-bottom:5px;">
		<b>{'common.gender'|devblocks_translate|capitalize}</b>:<br>
		<label><input type="radio" name="gender" value="M" {if $worker->gender == 'M'}checked="checked"{/if}> <span class="glyphicons glyphicons-male" style="color:rgb(2,139,212);"></span> {'common.gender.male'|devblocks_translate|capitalize}</label>
		&nbsp; 
		&nbsp; 
		<label><input type="radio" name="gender" value="F" {if $worker->gender == 'F'}checked="checked"{/if}> <span class="glyphicons glyphicons-female" style="color:rgb(243,80,157);"></span> {'common.gender.female'|devblocks_translate|capitalize}</label>
		&nbsp; 
		&nbsp; 
		<label><input type="radio" name="gender" value="" {if empty($worker->gender)}checked="checked"{/if}>  Not specified</label>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.location'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="location" size="64" value="{$worker->location}" placeholder="e.g. Los Angeles, CA USA"><br>
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.phone'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="phone" size="64" value="{$worker->phone}" placeholder="">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.mobile'|devblocks_translate|capitalize}</b>:<br>
		<input type="text" name="mobile" size="64" value="{$worker->mobile}" placeholder="">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.dob'|devblocks_translate|capitalize}</b>: <i>(YYYY-MM-DD)</i><br>
		<input type="text" name="dob" value="{if $worker->dob}{$worker->dob}{/if}" size="32" autocomplete="off" spellcheck="false" placeholder="1980-06-15">
	</div>
	
	<div style="margin-bottom:5px;">
		<b>{'common.photo'|devblocks_translate|capitalize}</b>:<br>
		<div style="float:left;margin-right:5px;">
			<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}" style="height:100px;width:100px;">
		</div>
		<div style="float:left;">
			<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}">{'common.edit'|devblocks_translate|capitalize}</button>
			<input type="hidden" name="avatar_image">
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.ui'|devblocks_translate|capitalize}</legend>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.assist'|devblocks_translate|capitalize}</b><br>
		<label><input type="checkbox" name="assist_mode" value="1" {if $prefs.assist_mode eq 1}checked{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
	</div>

	<div style="margin-bottom:5px;">
		<b>{'preferences.account.keyboard.shortcuts'|devblocks_translate|capitalize}</b><br>
		<label><input type="checkbox" name="keyboard_shortcuts" value="1" {if $prefs.keyboard_shortcuts eq 1}checked{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
	</div>
</fieldset>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	
	// Avatar chooser
	var $avatar_chooser = $frm.find('button.cerb-avatar-chooser');
	var $avatar_image = $avatar_chooser.parent().parent().find('img.cerb-avatar');
	ajax.chooserAvatar($avatar_chooser, $avatar_image);
	
	$frm.find('button.submit').on('click', function(e) {
		Devblocks.saveAjaxTabForm($frm);
	});
});
</script>