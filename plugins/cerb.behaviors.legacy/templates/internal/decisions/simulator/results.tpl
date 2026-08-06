{$nodes = $behavior_data.nodes}
{$tree = $behavior_data.tree}
{$depths = $behavior_data.depths}

<h3>Behavior: {$trigger->title}</h3>

<div>
	<div class="node trigger" style="margin-left:10px;">
		<div class="cerb-ui-tile">
			<span class="cerb-ui-tile--icon" style="background:var(--cerb-color-tag-blue);"><span class="cerb-icons cerb-icon-bot"></span></span>
			<div class="cerb-ui-tile--text">
				<div class="cerb-ui-tile--kind">event</div>
				<div class="cerb-ui-tile--name">{$event->manifest->name}</div>
			</div>
		</div>
		<div class="branch trigger" style="margin-left:10px;">
		{if is_array($tree[0]) && !empty($tree[0])}
			{foreach from=$tree[0] item=child_id}
				{include file="devblocks:cerb.behaviors.legacy::internal/decisions/simulator/branch.tpl" node_id=$child_id trigger_id=$trigger_id path=$behavior_path nodes=$nodes tree=$tree depths=$depths}
			{/foreach}
		{/if}
		</div>
	</div>
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
