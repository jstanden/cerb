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
			</td>
		</tr>
	</table>
</fieldset>

<div id="configAclItemized" style="display:block;{if $model->params.what != 'itemized'}display:none;{/if}">
	<ul>
		<li><a href="#roleEditorPrivsGeneral">{'common.global'|devblocks_translate|capitalize}</a></li>
		<li><a href="#roleEditorPrivsRecords">{'common.records'|devblocks_translate|capitalize}</a></li>
		<li><a href="#roleEditorPrivsPlugins">{'common.plugins'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="roleEditorPrivsGeneral">
		<div style="margin-bottom:10px;">
			<a href="javascript:;" style="font-size:90%;" onclick="checkAll('roleEditorPrivsGeneral');">check all</a>
		</div>
		
		{foreach from=$core_acl item=section}
			{if empty($section.privs)}
			{else}
			<fieldset class="peek black">
				<legend>
					<label>
					{$section.label}
					</label>
				</legend>
				
				<div style="padding-left:10px;">
					{foreach from=$section.privs item=priv key=priv_id}
						<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
					{/foreach}
				</div>
			</fieldset>
			{/if}
		{/foreach}
		
		<div style="margin-top:5px;margin-left:5px;">
			{foreach from=$core_acl.privs item=priv key=priv_id}
				<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
			{/foreach}
		</div>
	</div>
	
	<div id="roleEditorPrivsRecords">
	<div style="margin-bottom:10px;">
		<a href="javascript:;" style="font-size:90%;" onclick="checkAll('roleEditorPrivsRecords');">check all</a>
	</div>
	
	{$priv_labels = []}
	{$priv_labels['broadcast'] = 'common.broadcast'|devblocks_translate|capitalize}
	{$priv_labels['comment'] = 'common.comment'|devblocks_translate|capitalize}
	{$priv_labels['create'] = 'common.create'|devblocks_translate|capitalize}
	{$priv_labels['delete'] = 'common.delete'|devblocks_translate|capitalize}
	{$priv_labels['export'] = 'common.export'|devblocks_translate|capitalize}
	{$priv_labels['import'] = 'common.import'|devblocks_translate|capitalize}
	{$priv_labels['merge'] = 'common.merge'|devblocks_translate|capitalize}
	{$priv_labels['update'] = 'common.update'|devblocks_translate|capitalize}
	{$priv_labels['update.bulk'] = 'common.update.bulk'|devblocks_translate|capitalize}
	{$priv_labels['watchers'] = 'common.watchers'|devblocks_translate|capitalize}
	
	{foreach from=$contexts item=context key=context_id}
		{$priv_prefix = "contexts.{$context_id}"}
		{$available_privs = $context->params.acl[0]}
		
		{if $available_privs}
		<fieldset class="peek black">
			<legend>
				<label>
				{$aliases = Extension_DevblocksContext::getAliasesForContext($contexts[$context_id])}
				{$aliases.plural|default:$context->name|capitalize}
				</label>
			</legend>
			
			<div id="contexts{$context_id}" style="padding-left:10px;">
				{foreach from=$available_privs item=null key=priv}
				{$priv_id = "{$priv_prefix}.{$priv}"}
				<label><input type="checkbox" name="acl_privs[]" value="{$priv_prefix}.{$priv}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv_labels.$priv}</label><br>
				{/foreach}
			</div>
		</fieldset>
		{/if}
	{/foreach}
	</div>
	
	<div id="roleEditorPrivsPlugins">
	<div style="margin-bottom:10px;">
		<a href="javascript:;" style="font-size:90%;" onclick="checkAll('roleEditorPrivsPlugins');">check all</a>
	</div>
	{foreach from=$plugins_acl item=plugin key=plugin_id}
		{if empty($plugin.privs)}
		{else}
		<fieldset class="peek black">
			<legend>
				<label>
				{$plugin.label}
				</label>
			</legend>
			
			<div id="privs{$plugin_id}" style="padding-left:10px">
				{foreach from=$plugin.privs item=priv key=priv_id}
					<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
				{/foreach}
			</div>
		</fieldset>
		{/if}
	{/foreach}
	</div>
</div>

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

<div class="buttons" style="margin-top:15px;">
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
		
		// Tabs
		$('#configAclItemized').tabs();
		
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
