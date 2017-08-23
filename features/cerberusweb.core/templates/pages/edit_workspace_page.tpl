{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_PAGE}
{* Capture the form, since we might drop it inside a tab set if this is a new page *}
{capture name=workspace_page_build}
<form action="#" method="post" id="frmEditWorkspacePage" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="doEditWorkspacePage">
<input type="hidden" name="id" value="{$workspace_page->id|default:0}">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($workspace_page)}<input type="hidden" name="do_delete" value="0">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:5px;">
	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.name'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="name" value="{$workspace_page->name}" size="35" style="width:100%;">
		</td>
	</tr>

	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.type'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{if !empty($workspace_page)}
				{$page_extension = DevblocksPlatform::getExtension($workspace_page->extension_id, false)}
				{if $page_extension}
					{$page_extension->params.label|devblocks_translate|capitalize}
				{/if}
			{else}
				<select name="extension_id">
					{if !empty($page_extensions)}
						{foreach from=$page_extensions item=page_extension}
							<option value="{$page_extension->id}">{$page_extension->params.label|devblocks_translate|capitalize}</option>
						{/foreach}
					{/if}
				</select>
			{/if}
		</td>
	</tr>

	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$workspace_page}
		</td>
	</tr>
</table>

{if !empty($workspace_page->id)}
<fieldset class="peek">
	<legend>Who's using this?</legend>
	
	{if !empty($page_users)}
	<ul class="bubbles">
	{foreach from=$page_users item=page_user_id}
	{$page_user = $workers.$page_user_id}
		{if $page_user}
		<li class="bubble-gray"><img src="{devblocks_url}c=avatars&context=worker&context_id={$page_user->id}{/devblocks_url}?v={$page_user->updated}" style="height:1.5em;width:1.5em;border-radius:0.75em;vertical-align:middle;"> <a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$page_user_id}">{$page_user->getName()}</a></li>
		{/if}
	{/foreach}
	</ul>
	{else}
	{'common.nobody'|devblocks_translate|lower}
	{/if}
</fieldset>
{/if}

{if !empty($workspace_page) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to permanently delete this workspace page?</legend>
	
	<p>
		This will also delete all of the page's tabs and worklists.
	</p>
	
	<button type="button" class="red" onclick="$('#frmEditWorkspacePage').find('input:hidden[name=do_delete]').val('1');genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','{$view_id}',false,'workspace_delete');">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="toolbar">
	{if (!$workspace_page && $active_worker->hasPriv("contexts.{$peek_context}.create")) || ($workspace_page && $active_worker->hasPriv("contexts.{$peek_context}.update"))}<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','{$view_id}',false,'workspace_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>{/if}
	{if !empty($workspace_page) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).closest('div.toolbar').fadeOut().siblings('fieldset.delete').fadeIn();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>
{/capture}

{* Draw tabs if we're adding a new behavior *}
{if empty($workspace_page)}
<div class="tabs">
	<ul>
		<li><a href="#tabWorkspacePage{$workspace_page->id}Build">Build</a></li>
		{if $active_worker->hasPriv("contexts.{$peek_context}.import")}<li><a href="#tabWorkspacePage{$workspace_page->id}Import">Import</a></li>{/if}
	</ul>
	
	<div id="tabWorkspacePage{$workspace_page->id}Build">
		{$smarty.capture.workspace_page_build nofilter}
	</div>
	
	{if $active_worker->hasPriv("contexts.{$peek_context}.import")}
	<div id="tabWorkspacePage{$workspace_page->id}Import">
		<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWorkspacePageImport" onsubmit="return false;">
		<input type="hidden" name="c" value="pages">
		<input type="hidden" name="a" value="importWorkspacePageJson">
		<input type="hidden" name="view_id" value="{$view_id}">
		<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

		<div class="import">
			<b>Import:</b> (.json format)
			<br>
			<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false"></textarea>
		</div>
		
		<div style="margin-top:5px;">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			<br>
			<select name="owner">
				<option value="{CerberusContexts::CONTEXT_WORKER}:{$active_worker->id}">me</option>
				
				<option value="{CerberusContexts::CONTEXT_APPLICATION}:0">Application: Cerb</option>
				
				{if !empty($owner_groups)}
				{foreach from=$owner_groups item=group key=group_id}
					<option value="{CerberusContexts::CONTEXT_GROUP}:{$group_id}">Group: {$group->name}</option>
				{/foreach}
				{/if}
				
				{if !empty($owner_roles)}
				{foreach from=$owner_roles item=role key=role_id}
					<option value="{CerberusContexts::CONTEXT_ROLE}:{$role_id}">Role: {$role->name}</option>
				{/foreach}
				{/if}
				
				{if $active_worker->is_superuser}
				{foreach from=$workers item=worker key=worker_id}
					{if empty($worker->is_disabled)}
					<option value="{CerberusContexts::CONTEXT_WORKER}:{$worker_id}">Worker: {$worker->getName()}</option>
					{/if}
				{/foreach}
				{/if}
			</select>
		</div>
		
		<div class="config"></div>
		
		<div style="margin-top:10px;">
			<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.continue'|devblocks_translate|capitalize}</button>
		</div>
		</form>
	</div>
	{/if}
</div>
{else}{* Otherwise, just draw the form to edit an existing page *}
	{$smarty.capture.workspace_page_build nofilter}
{/if}

<script type="text/javascript">
$(function() {
	var $frm = $('#frmEditWorkspacePage');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{if !empty($workspace_page)}Edit Page{else}Add Page{/if}");
		$('#frmEditWorkspacePage').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
		
		$frm.find('input:text:first').focus().select();
		
		{if empty($trigger_id)}
		$popup.find('div.tabs').tabs();
		
		var $frm_import = $('#frmWorkspacePageImport');
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		$frm_import.find('button.submit').click(function() {
			genericAjaxPost('frmWorkspacePageImport','','', function(json) {
				if(json.config_html) {
					var $frm_import = $('#frmWorkspacePageImport');
					$frm_import.find('div.import').hide();
					$frm_import.find('div.config').hide().html(json.config_html).fadeIn();
					
				} else {
					if(json.page_url) {
						document.location.href = json.page_url;
					} else {
						genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspacePage','{$view_id}', false, 'workspace_import');
					}
				}
				
			});
		});
		{/if}
		
		// Owners
		
		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
		});
		
		$owners_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$owners_menu.hide();
				
				// Build bubble
				
				var context_data = token.split(':');
				var $li = $('<li/>');
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				var $hidden = $('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				var $a = $('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});
	});
});
</script>
