{$page_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$page_context_id = $event->id}

<h1 style="margin-left:10px;">{$event->name}</h1>

<form action="javascript:;">
<fieldset class="properties">
	<legend>Calendar Event</legend>
	{if !empty($properties)}
	{foreach from=$properties item=v key=k name=props}
		<div class="property">
			{if $k == '...'}
				<b>{$translate->_('...')|capitalize}:</b>
				...
			{elseif $k == 'date_start' || $k == 'date_end'}
				<b>{$v.label}:</b>
				{$v.value|devblocks_date}
			{else}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
			{/if}
		</div>
		{if $smarty.foreach.props.iteration % 3 == 0 && !$smarty.foreach.props.last}
			<br clear="all">
		{/if}
	{/foreach}
	<br clear="all">
	{/if}
	
	<div style="margin-top:5px;">
		<!-- Macros -->
		{if $active_worker->is_superuser}
			{if !empty($page_context) && !empty($page_context_id) && !empty($macros)}
				{devblocks_url assign=return_url full=true}c=profiles&tab=calendar_event&id={$page_context_id}-{$event->name|devblocks_permalink}{/devblocks_url}
				{include file="devblocks:cerberusweb.core::internal/macros/display/button.tpl" context=$page_context context_id=$page_context_id macros=$macros return_url=$return_url}
			{/if}
		{/if}
	
		{if $active_worker->is_superuser}			
			<button type="button" id="btnProfileEventEdit"><span class="cerb-sprite sprite-document_edit"></span> {'common.edit'|devblocks_translate|capitalize}</button>
		{/if}
	</div>
</fieldset>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div id="profileTabs">
	<ul>
		{$tabs = []}
		{$point = "cerberusweb.profiles.calendar_event.{$event->id}"}
		
		{$tabs[] = 'comments'}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$page_context}&id={$page_context_id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize}</a></li>
		
		{*
		{$tabs[] = 'members'}
		<li><a href="#members">Members</a></li>
		*}
	</ul>

	{*	
	<div id="members">
	</div>
	*}
</div> 

<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
$(function() {
	var tabs = $("#profileTabs").tabs( { selected:{$selected_tab_idx} } );

	{if $active_worker->is_superuser}
	$('#btnProfileEventEdit').bind('click', function() {
		$popup = genericAjaxPopup('event','c=internal&a=showCalendarEventPopup&context={$event->owner_context}&context_id={$event->owner_context_id}&event_id={$event->id}',null,false,'600');
		$popup.one('calendar_event_save', function(event) {
			event.stopPropagation();
			document.location.href.reload();
			//document.location.href = '{devblocks_url}c=profiles&k=event&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}';
		});
	});
	{/if}
	
	{include file="devblocks:cerberusweb.core::internal/macros/display/menu_script.tpl"}
});
</script>