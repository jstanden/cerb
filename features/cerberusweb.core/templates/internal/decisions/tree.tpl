<fieldset>
<legend style="{if $trigger->is_disabled}color:rgb(150,150,150);{/if}">{$trigger->title} {if $trigger->is_disabled}({'common.disabled'|devblocks_translate|capitalize}){/if}</legend>

{* [TODO] Use cache!! *}
{$tree_data = $trigger->getDecisionTreeData()}
{$tree_nodes = $tree_data.nodes}
{$tree_hier = $tree_data.tree}
{$tree_depths = $tree_data.depths}

<div class="badge badge-lightgray">
	<a href="javascript:;" onclick="decisionNodeMenu(this,'0','{$trigger->id}');" style="font-weight:bold;color:rgb(0,0,0);text-decoration:none;">
		{$event->name} &#x25be;
	</a>
</div>
<div style="margin-left:10px;">
	{foreach from=$tree_hier[0] item=child_id}
		{include file="devblocks:cerberusweb.core::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger->id data=$tree_data nodes=$tree_nodes tree=$tree_hier depths=$tree_depths}
	{/foreach}
</div>
</fieldset>