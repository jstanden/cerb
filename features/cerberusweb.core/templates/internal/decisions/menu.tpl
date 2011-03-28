<ul class="cerb-popupmenu cerb-float">
	{if empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionPopup&trigger_id={$trigger_id}',null,false,'500');">Edit Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionDeletePopup&trigger_id={$trigger_id}',null,false,'500');">Delete Behavior</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionReorderPopup&trigger_id={$trigger_id}',null,false,'500');">Reorder</a></li>
	{else}
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionPopup&id={$node->id}',null,false,'500');">Edit</a></li>
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionDeletePopup&id={$node->id}',null,false,'500');">Delete</a></li>
		{if $node->node_type != 'action'}<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionReorderPopup&id={$node->id}',null,false,'500');">Reorder</a></li>{/if}
		{*<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionMovePopup&id={$node->id}',null,true,'500');">Move</a></li>*}
	{/if}

	{if $node->node_type != 'action'}
		<li><hr></li>
		
		{if $node->node_type == 'outcome' || empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=switch',null,false,'500');">Add Decision</a></li>
		{/if}
		
		{if $node->node_type == 'switch'}
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=outcome',null,false,'500');">Add Outcome</a></li>
		{/if}
		
		{if $node->node_type == 'outcome' || empty($node)}
		<li><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showDecisionPopup&parent_id={$node->id}&trigger_id={$trigger_id}&type=action',null,false,'500');">Add Actions</a></li>
		{/if}
	{/if}
</ul>
