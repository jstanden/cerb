{$scheduled_behavior = DAO_ContextScheduledBehavior::getByContext($context, $context_id)}
{$trigger_events = DAO_TriggerEvent::getAll()}

{if !empty($scheduled_behavior)}
<fieldset class="properties" style="color:rgb(100,100,100);">
	{$target_ext = DevblocksPlatform::getExtension($context, false)}
	<legend>Upcoming scheduled behavior on this {$target_ext->name|lower}</legend>

	<table cellpadding="3" cellspacing="2" border="0" width="100%">
	{foreach from=$scheduled_behavior item=v key=k name=behaviors}
		{$behavior_context = $trigger_events.{$v->behavior_id}->owner_context}
		{$behavior_context_id = $trigger_events.{$v->behavior_id}->owner_context_id}
	
		<tr {if !$expanded && $smarty.foreach.behaviors.iteration > 5}style="display:none;"{/if}>
			<td valign="top" align="right" width="1%" nowrap="nowrap">
				<div class="badge badge-lightgray" title="{$v->run_date|devblocks_date}">
					{if $v->run_date < time()}
						now
					{else}
						{$v->run_date|devblocks_prettytime}
					{/if}
				</div>
			</td>
			<td valign="top" align="right" width="1%" nowrap="nowrap">
				{$ext = Extension_DevblocksContext::get($behavior_context)}
				{if !empty($ext)}
					{$meta = $ext->getMeta($behavior_context_id)}
					{$meta.name} 
					({$ext->manifest->name|capitalize})
				{/if}
			</td>
			<td valign="top" width="99%">
				<a href="javascript:;" onclick="$popup=genericAjaxPopup('peek','c=internal&a=showMacroSchedulerPopup&job_id={$k}',this,true,'400');var $container=$(this).closest('fieldset').parent();$popup.one('behavior_save',function(e) { genericAjaxGet($container, 'c=internal&a=renderContextScheduledBehavior&context={$context}&context_id={$context_id}'); });">{$trigger_events.{$v->behavior_id}->title}</a>
			</td>
		</tr>
	{/foreach}
	
	{if !$expanded && $smarty.foreach.behaviors.total > 5}
		<tr>
			<td></td>
			<td>
				<a href="javascript:;" style="font-weight:bold;" onclick="$(this).closest('fieldset').find('table tr:hidden').show();$(this).remove();">show all {$smarty.foreach.behaviors.total}</a>
			</td>
		</tr>
	{/if}
	</table>
	
</fieldset>
{/if}