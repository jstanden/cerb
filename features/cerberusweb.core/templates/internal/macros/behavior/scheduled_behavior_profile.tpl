{$scheduled_behavior = DAO_ContextScheduledBehavior::getByContext($context, $context_id)}
{$trigger_events = DAO_TriggerEvent::getAll()}

{if !empty($scheduled_behavior)}
<fieldset class="properties" style="color:rgb(100,100,100);">
	{$target_ext = DevblocksPlatform::getExtension($context, false)}
	<legend>Upcoming scheduled behavior on this {$target_ext->name|lower}</legend>

	<table cellpadding="3" cellspacing="2" border="0" width="100%">
	{foreach from=$scheduled_behavior item=v key=k name=behaviors}
		{$behavior = DAO_TriggerEvent::get($v->behavior_id)}
		{if $behavior}
		<tr {if !$expanded && $smarty.foreach.behaviors.iteration > 5}style="display:none;"{/if}>
			<td valign="middle" align="right" width="1%" nowrap="nowrap">
				<abbr title="{$v->run_date|devblocks_date}">
					{if $v->run_date < time()}
						now
					{else}
						{$v->run_date|devblocks_prettytime}
					{/if}
				</abbr>
			</td>
			<td valign="middle" align="right" width="1%" nowrap="nowrap">
				{$va = $behavior->getBot()}
				
				{if $va}
					<ul class="bubbles">
						<li>
							<img src="{devblocks_url}c=avatars&context=bot&context_id={$va->id}{/devblocks_url}?v={$va->updated_at}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
							<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BOT}" data-context-id="{$va->id}">{$va->name}</a>
						</li>
					</ul>
				{/if}
			</td>
			<td valign="middle" width="98%">
				<a href="javascript:;" onclick="$popup=genericAjaxPopup('peek','c=internal&a=showMacroSchedulerPopup&job_id={$k}',null,false,'50%');var $container=$(this).closest('fieldset').parent();$popup.one('behavior_save',function(e) { genericAjaxGet($container, 'c=internal&a=renderContextScheduledBehavior&context={$context}&context_id={$context_id}'); });" style="font-weight:bold;">{$trigger_events.{$v->behavior_id}->title}</a>
			</td>
		</tr>
		{/if}
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