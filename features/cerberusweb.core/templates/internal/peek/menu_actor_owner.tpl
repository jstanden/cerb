{*
 * Owner (actor) picker — a CerbUI.ContextChooser.Owner: switch type (Me / Everyone / Role / Group / Worker)
 * via the chip-head, then autocomplete. Posts the single hidden `name="owner"` value "context:id" (unchanged
 * save contract). "Everyone" (app:0) is offered only to superusers. Replaces the old nested actor menu;
 * each consumer just {include}s this with model=$model, and may pass an optional default_context to set
 * the initial searchable type (e.g. encourage role ownership) without reordering the menu.
 *}
{$oc_uid = uniqid('ownerchooser')}

{* Forced direct-pick avatars (looked up server-side): "Everyone" (app:0) + "Me" (the current worker) *}
{capture name=app_avatar}{devblocks_url}c=avatars&context=app&context_id=0{/devblocks_url}?v={$smarty.const.APP_BUILD}{/capture}
{capture name=me_avatar}{devblocks_url}c=avatars&context=worker&context_id={$active_worker->id|default:0}{/devblocks_url}?v={$smarty.const.APP_BUILD}{/capture}

{* Resolve the current owner (if any) for the chooser's initial value *}
{$owner_set = false}
{if $model && $model->owner_context}
	{$owner_ctx_ext = Extension_DevblocksContext::get($model->owner_context)}
	{if is_a($owner_ctx_ext, 'Extension_DevblocksContext')}
		{$owner_meta = $owner_ctx_ext->getMeta($model->owner_context_id)}
		{if $owner_meta}
			{$owner_set = true}
			{$owner_label = ($model->owner_context == 'cerberusweb.contexts.app') ? 'Everyone' : $owner_meta.name}
			{capture name=owner_avatar}{devblocks_url}c=avatars&context={$owner_ctx_ext->manifest->params.alias}&context_id={$model->owner_context_id}{/devblocks_url}?v={$owner_meta.updated|default:$smarty.const.APP_BUILD}{/capture}
		{/if}
	{/if}
{/if}

<div id="{$oc_uid}" class="cerb-ui-record-chooser">
{if $owner_set}
	<li data-context="{$model->owner_context}" data-context-id="{$model->owner_context_id}" data-label="{$owner_label}" data-image="{$smarty.capture.owner_avatar}"></li>
{/if}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
	if(!(window.CerbUI && CerbUI.ContextChooser && CerbUI.ContextChooser.Owner)) return;
	CerbUI.ContextChooser.Owner(document.getElementById('{$oc_uid}'), {
		meWorkerId: {$active_worker->id|default:0|json_encode nofilter},
		meLabel: {if $active_worker}{$active_worker->getName()|json_encode nofilter}{else}null{/if},
		meImageUrl: {$smarty.capture.me_avatar|json_encode nofilter},
		allowApp: {if $active_worker && $active_worker->is_superuser}true{else}false{/if},
		appImageUrl: {$smarty.capture.app_avatar|json_encode nofilter},
		defaultContext: {$default_context|default:''|json_encode nofilter}
		// Initial value is seeded as [data-context-id] markup inside the element (see above), not passed here.
	});
});
</script>
