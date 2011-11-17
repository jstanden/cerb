<form action="#" method="POST" style="margin-bottom:5px;" id="frmAddTabs" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doAddTab">
<input type="hidden" name="point" value="{$point}">

<div style="margin-bottom:5px;">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Create Workspace</button>
</div>

<fieldset>
	<legend>Display tabs on this page for these custom workspaces:</legend>
	
	<b>Show:</b> 
	<label>
		<input name="filter" type="radio" onclick="$ul=$(this).closest('fieldset').find('ul:first');$ul.find('> li').fadeIn();">
		All
	</label>
	<label>
		<input name="filter" type="radio" checked="checked" onclick="$ul=$(this).closest('fieldset').find('ul:first');$ul.find('> li').show(); $ul.find('> li:not(.mine)').fadeOut();">
		Mine Only
	</label>
	<label>
		<input name="filter" type="radio" onclick="$ul=$(this).closest('fieldset').find('ul:first');$ul.find('> li').hide(); $ul.find('> li:has(input:checked)').fadeIn();">
		Selected Only
	</label>
	
	<label>
		<input name="filter" type="radio">
		Matching
		<input type="text" size="24" id="workspace_lookup">
	</label>
	
	<ul style="list-style:none;margin:0px;margin-top:5px;padding-left:0px;">
	{foreach from=$workspaces item=workspace}
	{if $workspace->isReadableByWorker($active_worker)}
		<li class="drag {if $workspace->owner_context==CerberusContexts::CONTEXT_WORKER && $workspace->owner_context_id==$active_worker->id}mine{/if}" style="{if $workspace->owner_context==CerberusContexts::CONTEXT_WORKER && $workspace->owner_context_id==$active_worker->id}{else}display:none;{/if}">
			<span class="ui-icon ui-icon-arrow-4" style="display:inline-block;vertical-align:middle;cursor:move;"></span>
			<label>
				<input type="checkbox" name="workspace_ids[]" value="{$workspace->id}" {if $enabled_workspaces.{$workspace->id}}checked="checked"{/if}>
				<ul class="bubbles">
					<li><b>{$workspace->name}</b></li>
				</ul>
			</label>
			
			 owned by  
			{if $workspace->owner_context==CerberusContexts::CONTEXT_GROUP && isset($groups.{$workspace->owner_context_id})}
				<b>{$groups.{$workspace->owner_context_id}->name}</b> (Group)
			{/if}
			
			{if $workspace->owner_context==CerberusContexts::CONTEXT_ROLE && isset($roles.{$workspace->owner_context_id})}
				<b>{$roles.{$workspace->owner_context_id}->name}</b> (Role)
			{/if}
			
			{if $workspace->owner_context==CerberusContexts::CONTEXT_WORKER && isset($workers.{$workspace->owner_context_id})}
				<b>{$workers.{$workspace->owner_context_id}->getName()}</b> (Worker)
			{/if}
			
			<div style="margin-left:45px;margin-bottom:5px;">
				{$worklists = $workspace->getWorklists()}
				{if !empty($worklists)}
				{foreach from=$worklists item=worklist name=worklists}
					<div class="badge badge-lightgray" style="border:0;">{$worklist->list_view->title}</div>
				{/foreach}
				{/if}
			</div>
		</li>
	{/if}
	{/foreach}
	</ul>
	<br>
	
	<button class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
</fieldset>

</form>

<script type="text/javascript">
	$frm = $('#frmAddTabs');
	$ul = $frm.find('UL');
	
	$ul.sortable({ 'items':'li.drag', 'placeholder':'ui-state-highlight', 'handle':'span.ui-icon-arrow-4, ul.bubbles' });
	
	$frm.find('#workspace_lookup')
		.keyup(function(e) {
			$parent = $(this).prevAll(':radio:checked');
			
			if($parent.length==0)
				return;
			
			term = $(this).val().toLowerCase();
			
			$ul=$(this).closest('fieldset').find('ul:first');
			
			$ul.find('> li').each(function(e) {
				if(-1 != $(this).text().toLowerCase().indexOf(term)) {
					$(this).fadeIn();
				} else {
					$(this).hide();
				}
			});
		})
		.focus(function(e) {
			$ul=$(this).closest('fieldset').find('ul:first');
			$ul.find('> li').show();
			$(this).val('').select();
		})
		;
	
	$('#frmAddTabs button.add').click(function() {
		$popup = genericAjaxPopup('peek','c=internal&a=showEditWorkspacePanel&id=0',null,true,'600');
		$popup.one('workspace_save',function(e) {
			// Reload tabs
			$tabs = $('#frmAddTabs').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
	});
	
	$('#frmAddTabs button.submit').click(function() {
		genericAjaxPost('frmAddTabs', '', '', function(e) {
			window.document.location.reload();
		});
	});
</script>