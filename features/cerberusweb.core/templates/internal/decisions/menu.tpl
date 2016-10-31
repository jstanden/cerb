<ul class="cerb-popupmenu cerb-float" style="display:block;">
	{if empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_trigger{$trigger_id}','c=internal&a=showDecisionPopup&trigger_id={$trigger_id}',null,false,'50%');">Edit Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$trigger_id}',null,false,'50%');">Simulate Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('export_behavior','c=internal&a=showBehaviorExportPopup&trigger_id={$trigger_id}',null,false,'50%');">Export Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionReorderPopup&trigger_id={$trigger_id}',null,false,'50%');">Reorder</a></li>
	{else}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_{$node->node_type}{$node->id}','c=internal&a=showDecisionPopup&id={$node->id}',null,false,'50%');">{'common.edit'|devblocks_translate|capitalize}</a></li>
		<li><a href="javascript:;" onclick="genericAjaxGet('','c=internal&a=doDecisionNodeDuplicate&id={$node->id}', function() { genericAjaxGet('decisionTree{$trigger_id}','c=internal&a=showDecisionTree&id={$trigger_id}'); });">{'common.duplicate'|devblocks_translate|capitalize}</a></li>
		{if $node->node_type != 'action'}<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionReorderPopup&id={$node->id}',null,false,'50%');">Reorder</a></li>{/if}
		{*<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionMovePopup&id={$node->id}',null,true,'50%');">{'common.move'|devblocks_translate|capitalize}</a></li>*}
	{/if}

	{if $node->node_type != 'action'}
		<li><hr></li>

		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_switch','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=switch',null,false,'50%');">Add Decision</a></li>
		{/if}
		
		{if $node->node_type == 'switch'}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_outcome','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=outcome',null,false,'50%');">Add Outcome</a></li>
		{/if}
		
		{if !$node || in_array($node->node_type, ['subroutine','outcome','loop'])}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_action','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=action',null,false,'50%');">Add Actions</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_loop','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=loop',null,false,'50%');">Add Loop</a></li>
		{/if}
		
		{if !$node}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_subroutine','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=subroutine',null,false,'50%');">Add Subroutine</a></li>
		{/if}

	{/if}
</ul>
