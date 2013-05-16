{$page_context = CerberusContexts::CONTEXT_CALENDAR_EVENT}
{$page_context_id = $event->id}

<form action="#" method="POST" id="frmCalEvtPeek" name="frmCalEvtPeek" onsubmit="return false;" class="calendar_popup">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveCalendarEventPopup">
<input type="hidden" name="event_id" value="{$event->id}">
{if !empty($view_id)}
<input type="hidden" name="view_id" value="{$view_id}">
{/if}
<input type="hidden" name="calendar_id" value="{$event->calendar_id}">
{if !empty($link_context)}
<input type="hidden" name="link_context" value="{$link_context}">
<input type="hidden" name="link_context_id" value="{$link_context_id}">
{/if}

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.name'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<input type="text" name="name" value="{$event->name}" style="width:98%;">
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">When: </td>
		<td width="100%">
			<div>
				<input type="text" name="date_start" value="{$event->date_start|devblocks_date:'M d Y h:ia'}" size="32">
				 until 
				<input type="text" name="date_end" value="{$event->date_end|devblocks_date:'M d Y h:ia'}" size="32">
			</div>
			
			<i>(e.g. "tomorrow 5pm", "+2 hours", "2011-04-27 5:00pm", "8am", "August 15", "next Thursday")</i>
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}: </td>
		<td width="100%">
			<label><input type="radio" name="is_available" value="1" {if empty($event) || $event->is_available}checked="checked"{/if}> Available</label>
			<label><input type="radio" name="is_available" value="0" {if !empty($event) && empty($event->is_available)}checked="checked"{/if}> Busy</label>
		</td>
	</tr>
	<tbody class="repeat">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"> Repeat:</td>
		<td width="100%">
			<label><input type="radio" name="repeat_freq" value="" {if empty($recurring->params.freq)}checked="checked"{/if}> Never</label>
			<label><input type="radio" name="repeat_freq" value="daily" {if $recurring->params.freq=='daily'}checked="checked"{/if}> Daily</label>
			<label><input type="radio" name="repeat_freq" value="weekly" {if $recurring->params.freq=='weekly'}checked="checked"{/if}> Weekly</label>
			<label><input type="radio" name="repeat_freq" value="monthly" {if $recurring->params.freq=='monthly'}checked="checked"{/if}> Monthly</label>
			<label><input type="radio" name="repeat_freq" value="yearly" {if $recurring->params.freq=='yearly'}checked="checked"{/if}> Yearly</label>
			
			<div class="terms" style="padding-top:5px;">
				<div class="daily" style="display:{if $recurring->params.freq=='daily'}block{else}none{/if};">
					<fieldset>
						Every 
						<input type="text" name="repeat_options[daily][every_n]" value="{if $recurring->params.freq == 'daily' && isset($recurring->params.options.every_n)}{$recurring->params.options.every_n}{else}1{/if}" size="2"> 
						day(s)
					</fieldset>
				</div>
				<div class="weekly" style="display:{if $recurring->params.freq=='weekly'}block{else}none{/if};">
					<fieldset>
						Every 
						<input type="text" name="repeat_options[weekly][every_n]" value="{if $recurring->params.freq == 'weekly' && isset($recurring->params.options.every_n)}{$recurring->params.options.every_n}{else}1{/if}" size="2"> 
						weeks(s) on:
						
						<table cellpadding="5" cellspacing="2" border="0" class="toggle_grid">
							<tr>
							{$day_time = strtotime("last Sunday")}
							{section loop=7 start=0 name=days}
								{$sel = $recurring->params.freq == 'weekly' && false !== in_array({$smarty.section.days.index}, $recurring->params.options.day)}
								<td {if $sel}class="selected"{/if}>
									<input type="checkbox" name="repeat_options[weekly][day][]" value="{$smarty.section.days.index}" {if $sel}checked="checked"{/if}>
									{$day_time|devblocks_date:'D'}
								</td>
								{$day_time = strtotime("tomorrow", $day_time)}
							{/section}
							</tr>
						</table>
						
					</fieldset>
				</div>
				<div class="monthly" style="display:{if $recurring->params.freq=='monthly'}block{else}none{/if};">
					<fieldset>
						Every 
						<input type="text" name="repeat_options[monthly][every_n]" value="{if $recurring->params.freq == 'monthly' && isset($recurring->params.options.every_n)}{$recurring->params.options.every_n}{else}1{/if}" size="2"> 
						month(s) on:
						
						<table cellpadding="5" cellspacing="2" border="0" class="toggle_grid">
							{section loop=32 start=1 name=days}
								{$sel = $recurring->params.freq == 'monthly' && false !== in_array({$smarty.section.days.index}, $recurring->params.options.day)}
								{if $smarty.section.days.iteration % 7 == 1}
									<tr>
								{/if}
									<td {if $sel}class="selected"{/if}>
										<input type="checkbox" name="repeat_options[monthly][day][]" value="{$smarty.section.days.iteration}" {if $sel}checked="checked"{/if}>
										{$smarty.section.days.iteration}
									</td>
								{if $smarty.section.days.last || $smarty.section.days.iteration % 7 == 0}
									</tr>
								{/if}
							{/section}
						</table>
					</fieldset>
				</div>
				<div class="yearly" style="display:{if $recurring->params.freq=='yearly'}block{else}none{/if};">
					<fieldset>
						Every 
						<input type="text" name="repeat_options[yearly][every_n]" value="{if $recurring->params.freq == 'yearly' && isset($recurring->params.options.every_n)}{$recurring->params.options.every_n}{else}1{/if}" size="2"> 
						year(s) in: 
						
						<table cellpadding="5" cellspacing="2" border="0" class="toggle_grid">
							{section loop=13 start=1 name=months}
								{$month_time = mktime(0,0,0,$smarty.section.months.iteration,1,0)}
								{$sel = $recurring->params.freq == 'yearly' && false !== in_array({$smarty.section.months.index}, $recurring->params.options.month)}
								{if $smarty.section.months.iteration % 4 == 1}
									<tr>
								{/if}
								<td {if $sel}class="selected"{/if}>
									<input type="checkbox" name="repeat_options[yearly][month][]" value="{$smarty.section.months.iteration}" {if $sel}checked="checked"{/if}>
									{$month_time|devblocks_date:'M'}
								</td>
								{if $smarty.section.months.last || $smarty.section.months.iteration % 4 == 0}
									</tr>
								{/if}
							{/section}
						</table>
					</fieldset>
				</div>
				
			</div>
		</td>
	</tr>
	</tbody>
	<tbody class="end" style="{if empty($recurring)}display:none;{/if}">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"> End:</td>
			<td width="100%">
				<label><input type="radio" name="repeat_end" value="" {if empty($recurring->params.end.term)}checked="checked"{/if}> Never</label>
				<label><input type="radio" name="repeat_end" value="after_n" {if $recurring->params.end.term=='after_n'}checked="checked"{/if}> After</label>
				<label><input type="radio" name="repeat_end" value="date" {if $recurring->params.end.term=='date'}checked="checked"{/if}> On Date</label>
				
				<div class="ends">
					<div class="end after_n" style="display:{if $recurring->params.end.term=='after_n'}block{else}none{/if};">
						<fieldset>
							<input type="text" name="repeat_ends[after_n][iterations]" value="{if $recurring->params.end.term=='after_n' && isset($recurring->params.end.options.iterations)}{$recurring->params.end.options.iterations}{/if}" size="2"> 
							time(s)
						</fieldset>
					</div>
					<div class="end date" style="display:{if $recurring->params.end.term=='date'}block{else}none{/if};">
						<fieldset>
							<input type="text" name="repeat_ends[date][on]" value="{if $recurring->params.end.term=='date' && isset($recurring->params.end.options.on)}{$recurring->params.end.options.on|devblocks_date:'M d Y h:ia'}{/if}" style="width:98%;"> 
						</fieldset>
					</div>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<br>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

{if !empty($event->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	{if $event->recurring_id}
	You are deleting a repeating event. Delete:
	<div style="margin:5px;">
		<label><input type="radio" name="delete_scope" value="this" checked="checked"> Only this event</label>
		<label><input type="radio" name="delete_scope" value="future"> Future occurrences</label>
		<label><input type="radio" name="delete_scope" value="all"> Past and future occurrences</label>
	</div>
	{else}
	<div>
		Are you sure you want to delete this event?
	</div>
	{/if}
	
	<button type="button" class="delete"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

{* [TODO] If the worker can edit this calendar *}
{if 1}
	<div class="buttons">
		{if $event->id && $event->recurring_id}
			<fieldset>
				<legend>You are editing a recurring event. Modify:</legend>
				<div style="margin:5px;">
					<label><input type="radio" name="edit_scope" value="this"> Only this event</label>
					<label><input type="radio" name="edit_scope" value="future" checked="checked"> Future occurrences</label>
				</div>
			</fieldset>
		{/if}
		
		<button type="button" class="save"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
		{if !empty($event->id)}
		<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
		{/if}
		
		{if !empty($event->id)}
		<div style="float:right"><a href="{devblocks_url}c=profiles&type=calendar_event&id={$event->name|devblocks_permalink}-{$event->id}{/devblocks_url}">view full record</a></div>
		{/if}
		
		<br clear="all">
	</div>
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#frmCalEvtPeek');
	$popup.one('popup_open',function(event,ui) {
		$this = $(this);
		$frm = $this.find('form');
		
		// Title
		
		$this.dialog('option','title', 'Calendar Event');
		$('#frmCalEvtPeek :input:text:first').focus();
		
		// Repeat freq
		
		$frm.find('input:radio[name=repeat_freq]').click(function(e) {
			$td = $(this).closest('td');
			$table = $td.closest('table');
			$terms = $td.find('div.terms');
			$val = $(this).val();
			
			$terms.find('> div').hide();

			if($val.length > 0) {
				$terms.find('div.'+$(this).val()).fadeIn();
				$table.find('tbody.end').show();
			} else {
				$table.find('tbody.end').hide();
			}
		});
		
		// Repeat end
		
		$frm.find('input:radio[name=repeat_end]').click(function(e) {
			$ends=$(this).closest('td').find('div.ends');
			$val = $(this).val();
			
			$ends.find('> div').hide();

			if($val.length > 0) {
				$ends.find('div.'+$(this).val()).fadeIn();
			}
		});
		
		// Modify recurring event
		
		$frm.find('DIV.buttons INPUT:radio[name=edit_scope]').change(function(e) {
			$frm = $(this).closest('form');
			$val = $(this).val();
			
			if($val == 'this') {
				$frm.find('tbody.repeat, tbody.end').hide();
			} else {
				$frm.find('tbody.repeat, tbody.end').show();
			}
		});	
		
		// Toggle grids
		
		$frm.find('TABLE.toggle_grid TR TD').click(function(e) {
			$td = $(this).closest('td');
			$td.disableSelection();
			
			if($td.is('.selected')) {
				$td.find('input:checkbox').removeAttr('checked');
			} else {
				$td.find('input:checkbox').attr('checked', 'checked');
			}
			
			$td.toggleClass('selected');
			
			e.stopPropagation();
		});
		
		// Save button
		
		$frm.find('button.save').click(function() {
			genericAjaxPost('frmCalEvtPeek','','c=internal&a=handleSectionAction&section=calendars&action=saveCalendarEventPopupJson',function(json) {
				$popup = genericAjaxPopupFind('#frmCalEvtPeek');
				if(null != $popup) {
					$layer = $popup.prop('id').substring(5);
					
					$event = jQuery.Event('calendar_event_save');
					if(json.event_id)
						$event.event_id = json.event_id;
					if(json.month)
						$event.month = json.month;
					if(json.year)
						$event.year = json.year;
					
					genericAjaxPopupClose($layer, $event);
					
					{if !empty($view_id)}
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					{/if}
				}
			});
		});
		
		$frm.find('button.delete').click(function() {
			genericAjaxPost('frmCalEvtPeek','','c=internal&a=handleSectionAction&section=calendars&action=saveCalendarEventPopupJson&do_delete=1',function(json) {
				$popup = genericAjaxPopupFind('#frmCalEvtPeek');
				if(null != $popup) {
					$layer = $popup.prop('id').substring(5);

					$event = jQuery.Event('calendar_event_delete');
					if(json.event_id)
						$event.event_id = json.event_id;
					
					genericAjaxPopupClose($layer, $event);
					
					{if !empty($view_id)}
					genericAjaxGet('view{$view_id}', 'c=internal&a=viewRefresh&id={$view_id}');
					{/if}
				}
			});
		});
	});
</script>