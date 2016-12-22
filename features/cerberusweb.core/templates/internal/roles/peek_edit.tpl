{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="role">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek" style="background:none;border:0;">
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>Apply to:</b></td>
			<td width="99%">
				<label><input type="radio" name="who" value="all" {if empty($model) || $model->params.who=='all'}checked="checked"{/if}> {'common.everyone'|devblocks_translate|capitalize}</label><br>
				
				{if !empty($groups)}
					{$role_is_groups = $model->params.who=='groups'}
					<label><input type="radio" name="who" value="groups" {if $role_is_groups}checked="checked"{/if}> These groups:</label><br>
					<div class="who_list" style="margin-left:10px;display:{if $role_is_groups}block{else}none{/if};" id="configAclWhoGroups">
					{foreach from=$groups item=group key=group_id}
						<label><input type="checkbox" name="group_ids[]" value="{$group_id}" {if $role_is_groups && in_array($group_id,$model->params.who_list)}checked="checked"{/if}> {$group->name}</label><br>
					{/foreach}
					</div>
				{/if}
				
				{if !empty($workers)}
					{$role_is_workers = $model->params.who=='workers'}
					<label><input type="radio" name="who" value="workers" {if $role_is_workers}checked="checked"{/if}> These workers:</label><br>
					<div class="who_list" style="margin-left:10px;display:{if $role_is_workers}block{else}none{/if};" id="configAclWhoWorkers">
					{foreach from=$workers item=worker key=worker_id}
						<label><input type="checkbox" name="worker_ids[]" value="{$worker_id}" {if $role_is_workers && in_array($worker_id,$model->params.who_list)}checked="checked"{/if}> {$worker->getName()}{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}</label><br>
					{/foreach}
					</div>
				{/if}
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.privileges'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<label><input type="radio" name="what" value="all" {if $model->params.what=='all'}checked="checked"{/if} onclick="$('#configAclItemized').hide();"> {'common.all'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="what" value="none" {if empty($model) || $model->params.what=='none'}checked="checked"{/if} onclick="$('#configAclItemized').hide();"> {'common.none'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="what" value="itemized" {if $model->params.what=='itemized'}checked="checked"{/if} onclick="$('#configAclItemized').show();"> Itemized:</label>
			
				<div id="configAclItemized" style="display:block;padding-top:5px;{if $model->params.what != 'itemized'}display:none;{/if}">
				{foreach from=$plugins_acl item=plugin key=plugin_id}
					{$plugin_priv = "plugin.{$plugin_id}"}
					<fieldset class="peek">
						<legend>
							<label>
							{if $plugin_id=="cerberusweb.core"}
								<input type="hidden" name="acl_privs[]" value="plugin.cerberusweb.core">
							{else}
								<input type="checkbox" name="acl_privs[]" value="{$plugin_priv}" {if isset($role_privs.$plugin_priv)}checked="checked"{/if} onchange="toggleDiv('privs{$plugin_id}',(this.checked)?'block':'none');">
							{/if}
							{$plugin.label}
							</label>
						</legend>
						
						<div id="privs{$plugin_id}" style="padding-left:10px;margin-bottom:5px;display:{if $plugin_id=="cerberusweb.core" || isset($role_privs.$plugin_priv)}block{else}none{/if}">
							<a href="javascript:;" style="font-size:90%;" onclick="checkAll('privs{$plugin_id}');">check all</a>
							
							<div style="margin-top:5px;margin-left:5px;">
								{foreach from=$plugin.privs item=priv key=priv_id}
									<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
								{/foreach}
							</div>
						</div>
					</fieldset>
				{/foreach}
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ROLE context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this role?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.role'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Radios
		var $who = $frm.find('input:radio[name=who]');
		var $who_groups = $('#configAclWhoGroups');
		var $who_workers = $('#configAclWhoWorkers');
		
		$who.on('change', function(e) {
			var $radio = $(this);
			
			$who_groups.hide();
			$who_workers.hide();
			
			if($radio.val() == 'groups') {
				$who_groups.fadeIn();
			} else if($radio.val() == 'workers') {
				$who_workers.fadeIn();
			}
		});
	});
});
</script>
