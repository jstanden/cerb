{$menu_id = uniqid()}
<ul id="{$menu_id}" class="cerb-popupmenu cerb-float" style="display:block;">
	{if empty($node)}
		<li><a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$trigger_id}" data-edit="true">Edit Behavior</a></li>
		<li><a data-cerb-action="simulate_tree">Simulate Behavior</a></li>
		<li><a data-cerb-action="export_tree">Export Behavior</a></li>
		<li><a data-cerb-action="reorder_tree">Reorder</a></li>
	{else}
		<li><a data-cerb-action="edit_node" data-cerb-node-id="{$node->id|default:0}" data-cerb-node-type="{$node->node_type}">{'common.edit'|devblocks_translate|capitalize}</a></li>
		<li><a data-cerb-action="duplicate_node" data-cerb-node-id="{$node->id|default:0}">{'common.duplicate'|devblocks_translate|capitalize}</a></li>
		{if $node->node_type != 'action'}
			<li><a data-cerb-action="reorder_node" data-cerb-node-id="{$node->id|default:0}">Reorder</a></li>
		{/if}
	{/if}

	{if !$node || $node->node_type != 'action'}
		<li><hr></li>

		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li><a data-cerb-action="decision_add" data-cerb-node-id="{$node->id|default:0}">Add Decision</a></li>
		{/if}
		
		{if $node && $node->node_type == 'switch'}
		<li><a data-cerb-action="outcome_add" data-cerb-node-id="{$node->id|default:0}">Add Outcome</a></li>
		{/if}
		
		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li><a data-cerb-action="action_add" data-cerb-node-id="{$node->id|default:0}">Add Actions</a></li>
		<li><a data-cerb-action="loop_add" data-cerb-node-id="{$node->id|default:0}">Add Loop</a></li>
		{/if}
		
		{if !$node}
		<li><a data-cerb-action="subroutine_add" data-cerb-node-id="{$node->id|default:0}">Add Subroutine</a></li>
		{/if}
	{/if}
	
	<li><hr></li>
	
	{if $node}
		{if $node->node_type != 'action'}<li><a data-cerb-action="node_import" data-cerb-node-id="{$node->id|default:0}">Import</a></li>{/if}
		<li><a data-cerb-action="node_export" data-cerb-node-id="{$node->id|default:0}">Export</a></li>
	{else}
		<li><a data-cerb-action="tree_import" data-cerb-node-id="{$node->id|default:0}">Import</a></li>
	{/if}
</ul>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $menu = $('#{$menu_id}');
	
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

	$menu.find('[data-cerb-action=simulate_tree]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('simulate_behavior','c=profiles&a=invoke&module=behavior&action=renderSimulatorPopup&trigger_id={$trigger_id}',null,false,'50%');
	});

	$menu.find('[data-cerb-action=export_tree]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('export_behavior','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$trigger_id}',null,false,'50%');
	});

	$menu.find('[data-cerb-action=reorder_tree]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('','c=profiles&a=invoke&module=behavior&action=renderDecisionReorderPopup&trigger_id={$trigger_id}',null,false,'50%');
	});

	$menu.find('[data-cerb-action=edit_node]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		let node_type = $(this).attr('data-cerb-node-type');
		genericAjaxPopup('node_' + node_type + node_id,'c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&id=' + encodeURIComponent(node_id),null,false,'50%');
	});

	$menu.find('[data-cerb-action=duplicate_node]').on('click', function(e) {
		e.stopPropagation();

		let node_id = $(this).attr('data-cerb-node-id');

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'behavior');
		formData.set('action', 'duplicateNode');
		formData.set('id', node_id);

		genericAjaxPost(formData,null,null,function() {
			genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
			$menu.hide();
		});
	});

	$menu.find('[data-cerb-action=reorder_node]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('','c=profiles&a=invoke&module=behavior&action=renderDecisionReorderPopup&id=' + encodeURIComponent(node_id),null,false,'50%');
	});

	$menu.find('[data-cerb-action=decision_add]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('node_switch','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$trigger_id}&type=switch',null,false,'75%');
	});

	$menu.find('[data-cerb-action=outcome_add]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('node_outcome','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$trigger_id}&type=outcome',null,false,'75%');
	});

	$menu.find('[data-cerb-action=action_add]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('node_action','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$trigger_id}&type=action',null,false,'75%');
	});

	$menu.find('[data-cerb-action=loop_add]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('node_loop','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$trigger_id}&type=loop',null,false,'75%');
	});

	$menu.find('[data-cerb-action=subroutine_add]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('node_subroutine','c=profiles&a=invoke&module=behavior&action=renderDecisionPopup&parent_id=' + encodeURIComponent(node_id) + '&trigger_id={$trigger_id}&type=subroutine',null,false,'75%');
	});

	$menu.find('[data-cerb-action=node_import]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('import_nodes','c=profiles&a=invoke&module=behavior&action=renderImportPopup&trigger_id={$trigger_id}&node_id=' + encodeURIComponent(node_id),null,false,'50%');
	});

	$menu.find('[data-cerb-action=node_export]').on('click', function(e) {
		e.stopPropagation();
		let node_id = $(this).attr('data-cerb-node-id');
		genericAjaxPopup('export_nodes','c=profiles&a=invoke&module=behavior&action=renderExportPopup&trigger_id={$trigger_id}&node_id=' + encodeURIComponent(node_id),null,false,'50%');
	});

	$menu.find('[data-cerb-action=tree_import]').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPopup('import_nodes','c=profiles&a=invoke&module=behavior&action=renderImportPopup&trigger_id={$trigger_id}',null,false,'50%');
	});
});
</script>