{$scheduled_behavior = DAO_ContextScheduledBehavior::getByContext($context, $context_id)}
{$fieldset_id = uniqid()}
{$trigger_events = DAO_TriggerEvent::getAll()}
{$target_ext = Extension_DevblocksContext::get($context|default:'', false)}

{if !empty($scheduled_behavior) && $target_ext}
<fieldset class="properties" style="color:var(--cerb-color-background-contrast-100);" id="{$fieldset_id}">
	<legend>Upcoming scheduled behavior on this {$target_ext->name|lower}</legend>

	<table cellpadding="3" cellspacing="2" border="0" width="100%">
	{foreach from=$scheduled_behavior item=v key=k name=behaviors}
		{$behavior = DAO_TriggerEvent::get($v->behavior_id)}
		{if $behavior}
		{$va = $behavior->getBot()}
		{if $va}
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
			<td valign="middle" width="99%">
				<ul class="bubbles">
					<li>
						<img src="{devblocks_url}c=avatars&context=bot&context_id={$va->id}{/devblocks_url}?v={$va->updated_at}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
						<a href="javascript:;" class="cerb-behavior-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR_SCHEDULED}" data-context-id="{$k}">{$behavior->title}</a>
					</li>
				</ul>
			</td>
		</tr>
		{/if}
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

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	var $container = $fieldset.parent();
	
	$fieldset.find('.cerb-behavior-trigger')
		.cerbPeekTrigger()
			.on('cerb-peek-saved cerb-peek-deleted', function(e) {
				genericAjaxGet($container, 'c=profiles&a=invoke&module=scheduled_behavior&action=renderContextScheduledBehavior&context={$context}&context_id={$context_id}');
			})
		;
});
</script>
{/if}