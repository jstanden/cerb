{$tab_context = CerberusContexts::CONTEXT_GROUP}
{$tab_context_id = $group->id}

{$tab_is_editable = $active_worker->is_superuser || $active_worker->isGroupManager($group->id)}
{$tab_uniqid = uniqid()}

{if $tab_is_editable}
<form action="#" method="post" style="margin:5px;" id="frm{$tab_uniqid}">
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-gear"></span> {'common.edit'|devblocks_translate|capitalize}</button>
</form>
{/if}

<div id="fieldsets{$tab_uniqid}" style="column-width:275px;">

{foreach from=$buckets item=bucket key=bucket_id}
<div class="cerb-ui-panel cerb-ui-panel--spaced" style="break-inside:avoid-column;margin:0 0 10px 0;">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$bucket->id}">{$bucket->name}</a></div>
	</div>

	<div>
		{foreach from=$members item=member}
		{$worker_id = $member->id}
		{$worker = $workers.$worker_id}
		{$responsibility_level = $responsibilities.$bucket_id.$worker_id}
		
		{if $worker}
		<div style="width:250px;display:block;margin:0 10px 10px 5px;">
			<label>
				<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}"><b>{$worker->getName()}</b></a> {if $worker->title}({$worker->title}){/if}
			</label>
			
			{include file="devblocks:cerberusweb.core::internal/cerb_ui/slider_readonly.tpl" value=$responsibility_level invert=true tick=true width='250px' track_height='10px' thumb='15px'}
			
		</div>
		{/if}
		
		{/foreach}

	</div>
</div>
{/foreach}

</div>

{if $tab_is_editable}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#frm{$tab_uniqid}');
	let $fieldsets = $('#fieldsets{$tab_uniqid}');

	Devblocks.formDisableSubmit($frm);
	
	$fieldsets.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	$frm.find('button').click(function() {
		// Open popup
		var $popup = genericAjaxPopup('peek', 'c=profiles&a=invokeWidget&widget_id={$widget->id}&action=renderPopup&context={$tab_context}&context_id={$tab_context_id}', null, false, '90%');
		
		// When the popup saves, reload the tab
		$popup.one('responsibilities_save', function() {
			window.CerbUI?.Tabs?.fromPanel($frm[0])?.refresh();
		});
		
	});
});
</script>
{/if}