<form action="{devblocks_url}{/devblocks_url}" method="POST" style="margin-bottom:5px;" id="frmAddTabs">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doAddTab">
<input type="hidden" name="point" value="{$point}">
<input type="hidden" name="request" value="{$request}">

<fieldset>
	<legend>Visible Workspaces</legend>
	
	<div style="margin-bottom:5px;">
		<b>Create new workspace:</b>
		<input type="text" name="new_workspace" value="" size="45">
		<button type="button" class="add" onclick="">+</button>
	</div>
	
	<ul style="list-style:none;margin:0px;padding-left:0px;" class="container">
	{foreach from=$workspaces item=workspace}
		<li class="drag" style="padding-bottom:5px;"><!--
			--><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span><!--
			--><label><input type="checkbox" name="workspace_ids[]" value="{$workspace->id}" {if $enabled_workspaces.{$workspace->id}}checked="checked"{/if}> <b>{$workspace->name}</b></label>
	
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

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
</form>

<script type="text/javascript">
	$('#frmAddTabs UL').sortable({ items: 'LI.drag', placeholder:'ui-state-highlight' });
	
	$('#frmAddTabs button.add').click(function() {
		$frm = $(this.form);
		$container = $frm.find('UL.container').first();
		$text = $(this).siblings('input:text');
		
		if(0 == $text.val().length)
			return;
		
		$new_li = $('<li class="drag" style="padding-bottom:5px;"></li>');
		$('<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>').appendTo($new_li);
		$('<label><input type="checkbox" name="workspace_ids[]" value="'+$text.val()+'" checked="checked"> <b>'+$text.val()+'</b></label>').appendTo($new_li);
		
		$new_li.prependTo($container);
		$text.val('').focus();
	});
</script>