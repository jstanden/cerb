<form action="#" method="POST" style="margin-bottom:5px;" id="frmAddTabs" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doAddTab">
<input type="hidden" name="point" value="{$point}">

<div style="margin-bottom:5px;">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> Create Workspace</button>
</div>

<fieldset>
	<legend>Show these custom workspaces here:</legend>
	
	<ul style="list-style:none;margin:0px;padding-left:0px;" class="container">
	{foreach from=$workspaces item=workspace}
		<li class="drag" style="padding-bottom:5px;"><!--
			--><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span><!--
			--><label><input type="checkbox" name="workspace_ids[]" value="{$workspace->id}" {if $enabled_workspaces.{$workspace->id}}checked="checked"{/if}> <b>{$workspace->name}</b></label>
			
			{if $workspace->owner_context==CerberusContexts::CONTEXT_GROUP}
			 - {$groups.{$workspace->owner_context_id}->name} (Group)
			{/if}
			
			{if $workspace->owner_context==CerberusContexts::CONTEXT_ROLE}
			 - {$roles.{$workspace->owner_context_id}->name} (Role)
			{/if}
	
			{$worklists = $workspace->getWorklists()}
			{if !empty($worklists)}
			<div style="margin-left:20px;">
				{foreach from=$worklists item=worklist name=worklists}
					{$worklist->list_view->title}{if !$smarty.foreach.worklists.last}; {/if}
				{/foreach}
			</div>
			{/if}
		</li>
	{/foreach}
	</ul>
	
	<button class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
</fieldset>

</form>

<script type="text/javascript">
	$('#frmAddTabs UL').sortable({ items: 'LI.drag', placeholder:'ui-state-highlight' });
	
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