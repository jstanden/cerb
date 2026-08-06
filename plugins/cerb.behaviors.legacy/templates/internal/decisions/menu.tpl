{$menu_id = uniqid()}
<ul id="{$menu_id}" hidden>
	{if empty($node)}
		<li data-cerb-action="edit_behavior" data-icon="pen"><a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$trigger_id}" data-edit="true">Edit Behavior</a></li>
		<li data-cerb-action="edit_history" data-icon="history">Edit History</li>
		<li data-cerb-action="simulate_tree" data-icon="play">Simulate Behavior</li>
		<li data-cerb-action="export_tree" data-icon="upload">Export Behavior</li>
		<li data-cerb-action="reorder_tree" data-icon="sort-asc">Reorder</li>
	{else}
		<li data-cerb-action="edit_node" data-cerb-node-id="{$node->id|default:0}" data-cerb-node-type="{$node->node_type}" data-icon="pen">{'common.edit'|devblocks_translate|capitalize}</li>
		<li data-cerb-action="duplicate_node" data-cerb-node-id="{$node->id|default:0}" data-icon="copy">{'common.duplicate'|devblocks_translate|capitalize}</li>
		{if $node->node_type != 'action'}
			<li data-cerb-action="reorder_node" data-cerb-node-id="{$node->id|default:0}" data-icon="sort-asc">Reorder</li>
		{/if}
	{/if}

	{if !$node || $node->node_type != 'action'}
		<li></li>

		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li data-cerb-action="decision_add" data-cerb-node-id="{$node->id|default:0}" data-icon="branch">Add Decision</li>
		{/if}

		{if $node && $node->node_type == 'switch'}
		<li data-cerb-action="outcome_add" data-cerb-node-id="{$node->id|default:0}" data-icon="signpost">Add Outcome</li>
		{/if}

		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li data-cerb-action="action_add" data-cerb-node-id="{$node->id|default:0}" data-icon="zap">Add Actions</li>
		<li data-cerb-action="loop_add" data-cerb-node-id="{$node->id|default:0}" data-icon="repeat">Add Loop</li>
		{/if}

		{if !$node}
		<li data-cerb-action="subroutine_add" data-cerb-node-id="{$node->id|default:0}" data-icon="function">Add Subroutine</li>
		{/if}
	{/if}

	<li></li>

	{if $node}
		{if $node->node_type != 'action'}<li data-cerb-action="node_import" data-cerb-node-id="{$node->id|default:0}" data-icon="download">Import</li>{/if}
		<li data-cerb-action="node_export" data-cerb-node-id="{$node->id|default:0}" data-icon="upload">Export</li>
	{else}
		<li data-cerb-action="tree_import" data-cerb-node-id="{$node->id|default:0}" data-icon="download">Import</li>
	{/if}
</ul>
