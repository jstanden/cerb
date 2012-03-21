{$nodes = $behavior_data.nodes}
{$tree = $behavior_data.tree}
{$depths = $behavior_data.depths}

{$node = $nodes[$node_id]}

<fieldset>
	<legend style="color:rgb(50,50,50);">{$trigger->title}</legend>
	
	{include file="devblocks:cerberusweb.core::internal/decisions/simulator/branch.tpl" node_id=0 trigger_id=$trigger_id path=$behavior_path nodes=$nodes tree=$tree depths=$depths}
</fieldset>
