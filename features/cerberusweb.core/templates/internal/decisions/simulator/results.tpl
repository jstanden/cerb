{$nodes = $behavior_data.nodes}
{$tree = $behavior_data.tree}
{$depths = $behavior_data.depths}

{$node = $nodes[$node_id]}

<fieldset>
	<legend style="color:rgb(50,50,50);">{$trigger->title}</legend>
	
	<div>
		{include file="devblocks:cerberusweb.core::internal/decisions/simulator/branch.tpl" node_id=0 trigger_id=$trigger_id path=$behavior_path nodes=$nodes tree=$tree depths=$depths}
	</div>
</fieldset>

{if !empty($simulator_output) && is_array($simulator_output)}
{foreach from=$simulator_output item=output}
	{if is_array($output)}
	<fieldset><!--
		-->{if $output.title}<legend>{$output.title}</legend>{/if}<!-- 
		--><pre class="emailbody" style="margin:0;">{$output.content}</pre><!--
	--></fieldset>
	{/if}
{/foreach}
{/if}
