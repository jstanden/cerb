<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;" id="frmAddTabs">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doAddTab">
<input type="hidden" name="point" value="{$point}">
<input type="hidden" name="request" value="{$request}">
<fieldset>
	<legend>Add Existing Workspaces</legend>
	
	<ul style="list-style:none;margin:0px;padding-left:0px;">
	{foreach from=$workspaces item=workspace}
		<li class="drag" style="padding-bottom:5px;">
			<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>
			<label><input type="checkbox" name="workspace_ids[]" value="{$workspace->id}" {if $enabled_workspaces.{$workspace->id}}checked="checked"{/if}> <b>{$workspace->name}</b></label>
	
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
</fieldset>

<fieldset>
	<legend>Create Workspace</legend>
	
	<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
	<input type="text" name="new_workspace" value="" size="45"><br>
</fieldset>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
</form>

<script type="text/javascript">
	$('#frmAddTabs UL').sortable({ items: 'LI.drag', placeholder:'ui-state-highlight' });
</script>