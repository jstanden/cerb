{$menu_id = uniqid()}
<ul id="{$menu_id}" class="cerb-popupmenu cerb-float" style="display:block;">
	{if empty($node)}
		<li><a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$trigger_id}" data-edit="true">Edit Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger_id}',null,false,'50%');">Simulate Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('export_behavior','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$trigger_id}',null,false,'50%');">Export Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('','c=profiles&a=invoke&module=behavior&action=renderDecisionReorderPopup&trigger_id={$trigger_id}',null,false,'50%');">Reorder</a></li>
	{else}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_{$node->node_type}{$node->id}','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id={$node->id}',null,false,'50%');">{'common.edit'|devblocks_translate|capitalize}</a></li>
		<li><a href="javascript:;" cerb-action="duplicate">{'common.duplicate'|devblocks_translate|capitalize}</a></li>
		{if $node->node_type != 'action'}
			<li><a href="javascript:;" onclick="genericAjaxPopup('','c=profiles&a=invoke&module=behavior&action=renderDecisionReorderPopup&id={$node->id}',null,false,'50%');">Reorder</a></li>
		{/if}
	{/if}

	{if $node->node_type != 'action'}
		<li><hr></li>

		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_switch','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=switch',null,false,'75%');">Add Decision</a></li>
		{/if}
		
		{if $node->node_type == 'switch'}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_outcome','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=outcome',null,false,'75%');">Add Outcome</a></li>
		{/if}
		
		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_action','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=action',null,false,'75%');">Add Actions</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_loop','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=loop',null,false,'75%');">Add Loop</a></li>
		{/if}
		
		{if !$node}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_subroutine','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=subroutine',null,false,'75%');">Add Subroutine</a></li>
		{/if}
	{/if}
	
	<li><hr></li>
	
	{if $node}
		{if $node->node_type != 'action'}<li><a href="javascript:;" onclick="genericAjaxPopup('import_nodes','c=profiles&a=invoke&module=behavior&action=renderImportPopup&trigger_id={$trigger_id}&node_id={$node->id}',null,false,'50%');">Import</a></li>{/if}
		<li><a href="javascript:;" onclick="genericAjaxPopup('export_nodes','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$trigger_id}&node_id={$node->id}',null,false,'50%');">Export</a></li>
	{else}
		<li><a href="javascript:;" onclick="genericAjaxPopup('import_nodes','c=profiles&a=invoke&module=behavior&action=renderImportPopup&trigger_id={$trigger_id}',null,false,'50%');">Import</a></li>
	{/if}
</ul>

<script type="text/javascript">
$(function() {
	var $menu = $('#{$menu_id}');
	
	$menu
		.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
		})
		.on('cerb-peek-deleted', function() {
			document.location = '{devblocks_url}{/devblocks_url}';
		})
		;

	$menu.find('[cerb-action=duplicate]').on('click', function(e) {
		e.stopPropagation();

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'behavior');
		formData.set('action', 'duplicateNode');
		formData.set('id', '{$node->id}');

		genericAjaxPost(formData,null,null,function() {
			genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			$menu.hide();
		});
	});
});
</script>