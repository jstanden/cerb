{*
	Shared "managed by a workflow" warn callout for record peeks. Include from any peek whose record
	can be workflow-managed; the render method must assign `$workflow` (Model_Workflow) and optionally
	`$workflow_url`. Pass `noun` for the record's name in the sentence (defaults to "record").

	{include file="devblocks:cerberusweb.core::records/types/workflow/managed_callout.tpl" workflow=$workflow workflow_url=$workflow_url noun="section"}
*}
{if !empty($workflow)}
	<div class="cerb-ui-panel cerb-ui-panel--warn" style="margin-bottom:10px;">
		<div class="cerb-ui-header">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-nodes cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--title-sm">Managed by a workflow</div>
					<div class="cerb-ui-header--subtitle">This {$noun|default:'record'} is defined by the <b>{$workflow->name}</b> workflow. Changes made here will be overwritten the next time that workflow is imported.</div>
				</div>
			</div>
			{if !empty($workflow_url)}
				<div class="cerb-ui-header--right">
					<a href="{$workflow_url}" class="cerb-ui-button cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKFLOW}" data-context-id="{$workflow->id}" data-width="80%"><span class="cerb-icons cerb-icon-right-arrow"></span> Open workflow</a>
				</div>
			{/if}
		</div>
	</div>
{/if}
