<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="acl">
<input type="hidden" name="action" value="saveRole">
<input type="hidden" name="id" value="{if !empty($role->id)}{$role->id}{else}0{/if}">
<input type="hidden" name="do_delete" value="0">

{if $saved}
<div class="ui-widget">
	<div class="ui-state-highlight ui-corner-all" style="padding: 0.7em; margin: 0.2em; "> 
	<strong>Saved!</strong>
	<span style="float:right;">(<a href="javascript:;" onclick="$(this).closest('DIV.ui-widget').remove();">close</a>)</span>
	</div>
</div>
{/if}

<fieldset>
	<legend>
		{if empty($role->id)}
		Add Role
		{else}
		Modify '{$role->name}'
		{/if}
	</legend>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tr>
			<td colspan="2" style="padding-top:5px;">
				<b>Role Name:</b><br>
				<input type="text" name="name" value="{$role->name}" size="45" style="width:98%;">
			</td>
		</tr>
		
		<tr>
			<td colspan="2" style="padding-top:5px;">
				<b>Apply to:</b><br>
				
				<div style="margin-left:10px;">
					<label><input type="radio" name="who" value="all" {if empty($role) || $role->params.who=='all'}checked="checked"{/if}> Everyone</label><br>
					
					{if !empty($groups)}
						{$role_is_groups = $role->params.who=='groups'}
						<label><input type="radio" name="who" value="groups" {if $role_is_groups}checked="checked"{/if}> These groups:</label><br>
						<div class="who_list" style="margin-left:10px;display:{if $role_is_groups}block{else}none{/if};" id="configAclWhoGroups">
						{foreach from=$groups item=group key=group_id}
							<label><input type="checkbox" name="group_ids[]" value="{$group_id}" {if $role_is_groups && in_array($group_id,$role->params.who_list)}checked="checked"{/if}> {$group->name}</label><br>
						{/foreach}
						</div>
					{/if}
					
					{if !empty($workers)}
						{$role_is_workers = $role->params.who=='workers'}
						<label><input type="radio" name="who" value="workers" {if $role_is_workers}checked="checked"{/if}> These workers:</label><br>
						<div class="who_list" style="margin-left:10px;display:{if $role_is_workers}block{else}none{/if};" id="configAclWhoWorkers">
						{foreach from=$workers item=worker key=worker_id}
							<label><input type="checkbox" name="worker_ids[]" value="{$worker_id}" {if $role_is_workers && in_array($worker_id,$role->params.who_list)}checked="checked"{/if}> {$worker->getName()}{if !empty($worker->title)} (<span style="color:rgb(0,120,0);">{$worker->title}</span>){/if}</label><br>
						{/foreach}
						</div>
					{/if}
				</div>
			</td>
		</tr>
	
		<tr>
			<td colspan="2" style="padding-top:5px;">
				<b>Grant Permissions:</b><br>
			</td>
		</tr>
		
		<tr>
			<td width="100%" valign="top" colspan="2">
				<label><input type="radio" name="what" value="all" {if $role->params.what=='all'}checked="checked"{/if} onclick="$('#configAclItemized').hide();"> {'common.all'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="what" value="none" {if empty($role) || $role->params.what=='none'}checked="checked"{/if} onclick="$('#configAclItemized').hide();"> {'common.none'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="what" value="itemized" {if $role->params.what=='itemized'}checked="checked"{/if} onclick="$('#configAclItemized').show();"> Itemized:</label>
			
				<div id="configAclItemized" style="display:block;padding-top:5px;{if $role->params.what != 'itemized'}display:none;{/if}">
				{foreach from=$plugins item=plugin key=plugin_id}
					{if $plugin->enabled}
						{assign var=plugin_priv value="plugin."|cat:$plugin_id}
						<div style="margin-left:10px;background-color:rgb(255,255,221);border:2px solid rgb(255,215,0);padding:2px;margin-bottom:10px;">
						<label>
						{if $plugin->id=="cerberusweb.core"}
							<input type="hidden" name="acl_privs[]" value="plugin.cerberusweb.core">
						{elseif $plugin->id=="devblocks.core"}
							<input type="hidden" name="acl_privs[]" value="plugin.devblocks.core">
						{else}
							<input type="checkbox" name="acl_privs[]" value="{$plugin_priv}" {if isset($role_privs.$plugin_priv)}checked="checked"{/if} onchange="toggleDiv('privs{$plugin_id}',(this.checked)?'block':'none');">
						{/if}
						<b>{$plugin->name}</b></label><br>
							<div id="privs{$plugin_id}" style="padding-left:10px;margin-bottom:5px;display:{if $plugin->id=="cerberusweb.core" || isset($role_privs.$plugin_priv)}block{else}none{/if}">
							<a href="javascript:;" style="font-size:90%;" onclick="checkAll('privs{$plugin_id}');">{'check all'|devblocks_translate|lower}</a><br>
							{foreach from=$acl item=priv key=priv_id}
								{if $priv->plugin_id==$plugin_id}
								<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv->label|devblocks_translate}</label><br>
								{/if}
							{/foreach}
							</div>
						</div>
					{/if}
				{/foreach}
				</div>
			</td>
		</tr>
		
		<tr>
			<td colspan="2">
				<br>
				<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
				{if $active_worker->is_superuser}<button type="button" onclick="if(confirm('Are you sure you want to delete this role?')){literal}{{/literal}this.form.do_delete.value='1';this.form.submit();{literal}}{/literal}"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
			</td>
		</tr>
	</table>
</fieldset>

<script type="text/javascript">
$('#configRole INPUT:radio[name=who]').click(function(e) {
	$('#configRole DIV.who_list').hide();
	
	$val = $(this).val();
	
	if($val == 'groups') {
		$(this).closest('td').find('DIV.who_list:nth(0)').show();
	} else if($val == 'workers') {
		$(this).closest('td').find('DIV.who_list:nth(1)').show();
	}
	
});
</script>