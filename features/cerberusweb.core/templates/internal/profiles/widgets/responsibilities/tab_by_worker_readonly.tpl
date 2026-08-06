{$tab_context = CerberusContexts::CONTEXT_WORKER}
{$tab_context_id = $worker->id}

{$tab_is_editable = $active_worker->is_superuser}
{$tab_uniqid = uniqid()}

{if $tab_is_editable}
<form action="#" method="post" style="margin:5px;" id="frm{$tab_uniqid}">
	<button type="button" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-gear"></span> {'common.edit'|devblocks_translate|capitalize}</button>
</form>
{/if}

<div id="fieldsets{$tab_uniqid}" style="column-width:275px;">

{foreach from=$groups item=group key=group_id}
{if $worker->isGroupMember($group_id)}
<div class="cerb-ui-panel cerb-ui-panel--spaced" style="break-inside:avoid-column;margin:0 0 10px 0;">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$group->id}">{$group->name}</a></div>
	</div>

	<div>
		{foreach from=$group->getBuckets() item=bucket key=bucket_id}
		{$responsibility_level = $responsibilities.$bucket_id}
		<div style="width:250px;display:block;margin:0 10px 10px 5px;">
			<label>
				<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$bucket->id}"><b>{$bucket->name}</b></a>
			</label>
			
			{include file="devblocks:cerberusweb.core::internal/cerb_ui/slider_readonly.tpl" value=$responsibility_level invert=true tick=true width='250px' track_height='10px' thumb='15px'}
			
		</div>
		{/foreach}

	</div>
</div>
{/if}
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