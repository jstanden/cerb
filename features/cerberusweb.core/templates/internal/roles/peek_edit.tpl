{$peek_context = CerberusContexts::CONTEXT_ROLE}
{$peek_context_id = $model->id}

{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="role">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
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
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.image'|devblocks_translate|capitalize}:</b></td>
			<td width="99%" valign="top">
				<div style="float:left;margin-right:5px;">
					<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=role&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="height:50px;width:50px;">
				</div>
				<div style="float:left;">
					<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_ROLE}" data-context-id="{$model->id}">{'common.edit'|devblocks_translate|capitalize}</button>
					<input type="hidden" name="avatar_image">
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.membership'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<div>
					Grant role <b>privileges</b> to these workers:
					<textarea name="member_query_worker" placeholder="({'common.everyone'|devblocks_translate|lower})" style="width:100%;" data-editor-mode="ace/mode/cerb_query">{$model->member_query_worker}</textarea>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.ownership'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<div>
					Records owned by this role can be <b>edited</b> by these workers:
					<textarea name="editor_query_worker" placeholder="({'common.everyone'|devblocks_translate|lower})" style="width:100%;" data-editor-mode="ace/mode/cerb_query">{$model->editor_query_worker}</textarea>
				</div>

				<div>
					Records owned by this role are <b>visible</b> to these workers:
					<textarea name="reader_query_worker" placeholder="({'common.everyone'|devblocks_translate|lower})" style="width:100%;" data-editor-mode="ace/mode/cerb_query">{$model->reader_query_worker}</textarea>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.privileges'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				<label><input type="radio" name="privs_mode" value="all" {if $model->privs_mode=='all'}checked="checked"{/if} onclick="$('#configAclItemized').hide();"> {'common.all'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="privs_mode" value="" {if empty($model->id) || !$model->privs_mode}checked="checked"{/if} onclick="$('#configAclItemized').hide();"> {'common.none'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="privs_mode" value="itemized" {if $model->privs_mode=='itemized'}checked="checked"{/if} onclick="$('#configAclItemized').show();"> Itemized:</label>
			</td>
		</tr>
	</table>
</fieldset>

<div id="configAclItemized" style="display:block;{if $model->privs_mode != 'itemized'}display:none;{/if}">
	<ul>
		<li><a href="#roleEditorPrivsRecords">{'common.records'|devblocks_translate|capitalize}</a></li>
		<li><a href="#roleEditorPrivsOther">{'common.other'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="roleEditorPrivsOther">
		<div style="margin-bottom:10px;">
			<a href="javascript:;" style="font-size:90%;" onclick="checkAll('roleEditorPrivsOther');">check all</a>
		</div>
		
		{foreach from=$core_acl item=section}
			{if empty($section.privs)}
			{else}
			{$container_id = uniqid()}
			<fieldset class="peek black" style="break-inside:avoid-column;page-break-inside:avoid;">
				<legend onclick="checkAll('privs{$container_id}');">
					<label>
					{$section.label}
					</label>
				</legend>
				
				<div id="privs{$container_id}" style="padding-left:10px;">
					{foreach from=$section.privs item=priv key=priv_id}
						<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
					{/foreach}
				</div>
			</fieldset>
			{/if}
		{/foreach}
		
		{if $core_acl.privs}
		<div style="margin-top:5px;margin-left:5px;">
			{foreach from=$core_acl.privs item=priv key=priv_id}
				<label style=""><input type="checkbox" name="acl_privs[]" value="{$priv_id}" {if isset($role_privs.$priv_id)}checked{/if}> {$priv}</label><br>
			{/foreach}
		</div>
		{/if}
		
		{foreach from=$plugins_acl item=plugin key=plugin_id}
			{if empty($plugin.privs)}
			{else}
			<fieldset class="peek black" style="break-inside:avoid-column;page-break-inside:avoid;">
				<legend>
					<label onclick="checkAll('privs{$plugin_id}');">
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
	
	<div style="column-count:2;column-width:50%;">
	{foreach from=$contexts item=context key=context_id}
		{$priv_prefix = "contexts.{$context_id}"}
		{$available_privs = $context->params.acl[0]}
		
		{if $available_privs}
		<fieldset class="peek black" style="break-inside:avoid-column;page-break-inside:avoid;">
			<legend>
				<label onclick="checkAll('contexts{$context_id}');">
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
	</div>
</div>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this role?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:15px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_and_close'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" class="continue"><span class="glyphicons glyphicons-circle-arrow-right" style="color:rgb(0,180,0);"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.role'|devblocks_translate|capitalize|escape:'javascript' nofilter}");

		// Tabs
		var $tabs = $('#configAclItemized').tabs();

		$tabs.find('fieldset > legend').on('mousedown', function(e) {
			e.preventDefault();
		});
		
		// This prevents the popup from being stranded downward by the height of the roles popup after submit
		var hide_tabs_on_submit = function(e) {
			if(!e.error)
				$tabs.hide();
		};
		
		// Buttons
		$popup.find('button.submit').click({ after: hide_tabs_on_submit }, Devblocks.callbackPeekEditSave);
		$popup.find('button.continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Radios
		var $who = $frm.find('input:radio[name=who]');
		var $who_groups = $('#configAclWhoGroups');
		var $who_workers = $('#configAclWhoWorkers');
		
		// Avatar chooser
		
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
		
		// Editors
		$popup.find('textarea[data-editor-mode]')
			.cerbCodeEditor()
			.cerbCodeEditorAutocompleteSearchQueries({ context: 'worker' })
			.nextAll('pre.ace_editor')
			;
		
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
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
