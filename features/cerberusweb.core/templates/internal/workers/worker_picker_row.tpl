<tr data-worker-id="{$worker->id}" data-score="{$worker_meta[$worker->id].responsibility}">
	<td>
		{if $worker_meta[$worker->id].is_online}
		<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:rgb(0,180,0);margin-right:5px;line-height:10px;"></div>
		{else}
		<div style="display:inline-block;border-radius:10px;width:10px;height:10px;background-color:var(--cerb-color-background-contrast-230);margin-right:5px;line-height:10px;"></div>
		{/if}
		<img src="{devblocks_url}c=avatars&context=worker&context_id={$worker->id}{/devblocks_url}?v={$worker->updated}" style="height:25px;width:25px;border-radius:25px;" align="middle" loading="lazy">
		<a class="item no-underline"><b>{$worker->getName()}</b></a>
		<small>{$worker->title}</small>
		{if $worker_meta[$worker->id].is_selected}
		<input type="hidden" name="current_sample[]" value="{$worker->id}">
		<input type="hidden" name="initial_sample[]" value="{$worker->id}">
		{/if}
	</td>
	<td nowrap="nowrap">
		{$num_assignments = $worker_meta[$worker->id].workload.records.{CerberusContexts::CONTEXT_TICKET}|default:0 + $worker_meta[$worker->id].workload.records.{CerberusContexts::CONTEXT_TASK}|default:0}
		{if $num_assignments}{$num_assignments}{else}0{/if}
	</td>
	<td nowrap="nowrap">
		{$num_unread_notifications = $worker_meta[$worker->id].workload.records.{CerberusContexts::CONTEXT_NOTIFICATION}|default:0}
		{if $num_unread_notifications}{$num_unread_notifications}{else}0{/if}
	</td>
	{if $show_responsibilities}
	<td nowrap="nowrap">
			{include file="devblocks:cerberusweb.core::internal/cerb_ui/slider_readonly.tpl" value=$worker_meta[$worker->id].responsibility invert=true tick=true width='70px' track_height='10px' thumb='14px'}
	</td>
	{/if}
	<td nowrap="nowrap">
		<a class="delete" {if !$worker_meta[$worker->id].is_selected}style="display:none;"{/if}><span class="cerb-icons cerb-icon-circle-remove" style="font-size:14px;color:rgb(200,0,0);"></span></a>
	</td>
</tr>