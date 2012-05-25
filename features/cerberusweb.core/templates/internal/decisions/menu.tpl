<ul class="cerb-popupmenu cerb-float" style="display:block;">
	{if empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_trigger{$trigger_id}','c=internal&a=showDecisionPopup&trigger_id={$trigger_id}',null,false,'500');">Edit Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('simulate_behavior','c=internal&a=showBehaviorSimulatorPopup&trigger_id={$trigger_id}',null,false,'500');">Simulate Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionReorderPopup&trigger_id={$trigger_id}',null,false,'500');">Reorder</a></li>
	{else}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_{$node->node_type}{$node->id}','c=internal&a=showDecisionPopup&id={$node->id}',null,false,'500');">Edit</a></li>
		{if $node->node_type != 'action'}<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionReorderPopup&id={$node->id}',null,false,'500');">Reorder</a></li>{/if}
		{*<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionMovePopup&id={$node->id}',null,true,'500');">Move</a></li>*}
	{/if}

	{if $node->node_type != 'action'}
		<li><hr></li>

		{if $node->node_type == 'outcome' || empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_switch','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=switch',null,false,'500');">Add Decision</a></li>
		{/if}
		
		{if $node->node_type == 'switch'}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_outcome','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=outcome',null,false,'500');">Add Outcome</a></li>
		{/if}
		
		{if $node->node_type == 'outcome' || empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('node_action','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=action',null,false,'500');">Add Actions</a></li>
		{/if}
	{/if}
</ul>
