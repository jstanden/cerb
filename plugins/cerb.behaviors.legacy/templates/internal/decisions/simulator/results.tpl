{$nodes = $behavior_data.nodes}
{$tree = $behavior_data.tree}
{$depths = $behavior_data.depths}

<h3>Behavior: {$trigger->title}</h3>

<div>
	<div class="badge badge-lightgray" style="margin:2px;">
		<a style="text-decoration:none;font-weight:bold;color:var(--cerb-color-background-contrast-50);">
			{$event->manifest->name}
		</a>
	</div>

	{include file="devblocks:cerb.behaviors.legacy::internal/decisions/simulator/branch.tpl" node_id=0 trigger_id=$trigger_id path=$behavior_path nodes=$nodes tree=$tree depths=$depths}
</div>

{if !empty($simulator_output) && is_array($simulator_output)}
{$last_action = null}
{foreach from=$simulator_output item=output}
	{if is_array($output)}
	{if $output.action != $last_action}
		<h3>Action: {$output.action}</h3>
		{$last_action = $output.action}
	{/if}
	<fieldset class="block"><!--
		-->{if $output.title}<legend><a data-cerb-link="toggle" style="text-decoration:none;cursor:pointer;">{$output.title}</a></legend>{/if}<!--
		--><pre class="emailbody" dir="auto" style="margin:0;">{$output.content}</pre><!--
	--></fieldset>
	{/if}
{/foreach}
{/if}

<h3 style="margin-top:10px;">Log</h3>

{if !empty($conditions_output)}
<fieldset class="block black">
<pre class="emailbody" dir="auto">{$conditions_output}</pre>
</fieldset>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $div = $('#divBehaviorSimulatorResults{intval($trigger_id)}');

	$div.find('[data-cerb-link=toggle]').on('click', function(e) {
		e.stopPropagation();
		$(this).parent().next('pre').toggle();
	});
})
</script>
