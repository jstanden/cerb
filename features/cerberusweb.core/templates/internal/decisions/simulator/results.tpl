{$nodes = $behavior_data.nodes}
{$tree = $behavior_data.tree}
{$depths = $behavior_data.depths}

{$node = $nodes[$node_id]}

<h3>Behavior: {$trigger->title}</h3>

<div>
	<div class="badge badge-lightgray" style="margin:2px;">
		<a href="javascript:;" style="text-decoration:none;font-weight:bold;color:rgb(50,50,50);">
			{$event->manifest->name}
		</a>
	</div>

	{include file="devblocks:cerberusweb.core::internal/decisions/simulator/branch.tpl" node_id=0 trigger_id=$trigger_id path=$behavior_path nodes=$nodes tree=$tree depths=$depths}
</div>

{if !empty($simulator_output) && is_array($simulator_output)}
{$last_action = null}
{foreach from=$simulator_output item=output}
	{if is_array($output)}
	{if $output.action != $last_action}
		<h3>Action: {$output.action}</h3>
		{$last_action = $output.action}
	{/if}
	<fieldset><!--
		-->{if $output.title}<legend>{$output.title}</legend>{/if}<!--
		--><pre class="emailbody" style="margin:0;">{$output.content}</pre><!--
	--></fieldset>
	{/if}
{/foreach}
{/if}
