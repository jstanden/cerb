<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWatcherFilter" onsubmit="return false;">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="core.pref.notifications">
<input type="hidden" name="action" value="saveWatcherPanel">
<input type="hidden" name="id" value="{$filter->id}">

<b>Filter Name:</b> (e.g. Emergency Support to SMS)<br>
<input type="text" name="name" value="{$filter->name|escape}" size="45" style="width:95%;"><br>

{if $active_worker->is_superuser}
	{'common.worker'|devblocks_translate|capitalize}:
	<select name="worker_id" onchange="genericAjaxGet('div_do_email','c=preferences&a=handleTabAction&tab=core.pref.notifications&action=getWorkerAddresses&worker_id='+selectValue(this));">
		{foreach from=$all_workers item=worker key=worker_id}
			<option value="{$worker_id}" {if (empty($filter->worker_id) && $worker_id==$active_worker->id) || $filter->worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
		{/foreach}
	</select>
	 &nbsp; 
{else}
	<input type="hidden" name="worker_id" value="{if !empty($filter->worker_id)}{$filter->worker_id}{else}{$active_worker->id}{/if}">
{/if}

<label><input type="checkbox" name="is_disabled" value="1" {if $filter->is_disabled}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
<br>
<br>

<h2>In these mail events:</h2>
{assign var=crit_event value=$filter->criteria.event}
<input type="hidden" name="rules[]" value="event">
<label><input type="checkbox" name="value_event[]" value="mail_outgoing" {if isset($crit_event.mail_outgoing)}checked="checked"{/if}> Outgoing</label>
<label><input type="checkbox" name="value_event[]" value="mail_incoming" {if isset($crit_event.mail_incoming)}checked="checked"{/if}> Incoming</label>
<br>
<br>

<h2>If these criteria match:</h2>

{* Date/Time *}
{assign var=expanded value=false}
{if isset($filter->criteria.dayofweek) || isset($filter->criteria.timeofday)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockDateTime',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockDateTime',false);"> <b>Current Date/Time</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockDateTime">
	<tr>
		<td valign="top">
			{assign var=crit_dayofweek value=$filter->criteria.dayofweek}
			<label><input type="checkbox" id="chkRuleDayOfWeek" name="rules[]" value="dayofweek" {if !is_null($crit_dayofweek)}checked="checked"{/if}> Day of Week:</label>
		</td>
		<td valign="top">
			<label><input type="checkbox" name="value_dayofweek[]" value="0" {if $crit_dayofweek.sun}checked="checked"{/if}> {'Sunday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="1" {if $crit_dayofweek.mon}checked="checked"{/if}> {'Monday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="2" {if $crit_dayofweek.tue}checked="checked"{/if}> {'Tuesday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="3" {if $crit_dayofweek.wed}checked="checked"{/if}> {'Wednesday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="4" {if $crit_dayofweek.thu}checked="checked"{/if}> {'Thursday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="5" {if $crit_dayofweek.fri}checked="checked"{/if}> {'Friday'|date_format:'%a'}</label>
			<label><input type="checkbox" name="value_dayofweek[]" value="6" {if $crit_dayofweek.sat}checked="checked"{/if}> {'Saturday'|date_format:'%a'}</label>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_timeofday value=$filter->criteria.timeofday}
			<label><input type="checkbox" id="chkRuleTimeOfDay" name="rules[]" value="timeofday" {if !is_null($crit_timeofday)}checked="checked"{/if}> Time of Day:</label>
		</td>
		<td valign="top">
			<i>from</i> 
			<select name="timeofday_from">
				{section start=0 loop=24 name=hr}
				{section start=0 step=30 loop=60 name=min}
					{assign var=hr value=$smarty.section.hr.index}
					{assign var=min value=$smarty.section.min.index}
					{if 0==$hr}{assign var=hr value=12}{/if}
					{if $hr>12}{math assign=hr equation="x-12" x=$hr}{/if}
					{assign var=val value=$smarty.section.hr.index|cat:':'|cat:$smarty.section.min.index}
					<option value="{$val}" {if $crit_timeofday.from==$val}selected="selected"{/if}>{$hr|string_format:"%d"}:{$min|string_format:"%02d"} {if $smarty.section.hr.index<12}AM{else}PM{/if}</option>
				{/section}
				{/section}
			</select>
			 <i>to</i> 
			<select name="timeofday_to">
				{section start=0 loop=24 name=hr}
				{section start=0 step=30 loop=60 name=min}
					{assign var=hr value=$smarty.section.hr.index}
					{assign var=min value=$smarty.section.min.index}
					{if 0==$hr}{assign var=hr value=12}{/if}
					{if $hr>12}{math assign=hr equation="x-12" x=$hr}{/if}
					{assign var=val value=$smarty.section.hr.index|cat:':'|cat:$smarty.section.min.index}
					<option value="{$val}" {if $crit_timeofday.to==$val}selected="selected"{/if}>{$hr|string_format:"%d"}:{$min|string_format:"%02d"} {if $smarty.section.hr.index<12}AM{else}PM{/if}</option>
				{/section}
				{/section}
			</select>
		</td>
	</tr>
</table>

{* Ticket *}
{assign var=expanded value=false}
{if isset($filter->criteria.mask) || isset($filter->criteria.groups) || isset($filter->criteria.next_worker_id)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockTicket',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockTicket',false);"> <b>Ticket</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockTicket">
	<tr>
		<td valign="top">
			{assign var=crit_mask value=$filter->criteria.mask}
			<label><input type="checkbox" id="chkRuleMask" name="rules[]" value="mask" {if !is_null($crit_mask)}checked="checked"{/if}> Mask:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_mask" size="45" value="{$crit_mask.value|escape}" onchange="document.getElementById('chkRuleMask').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_next_worker value=$filter->criteria.next_worker_id}
			<label><input type="checkbox" id="chkRuleNextWorkerId" name="rules[]" value="next_worker_id" {if !is_null($crit_next_worker)}checked="checked"{/if}> Assigned to:</label>
		</td>
		<td valign="top">
			<select name="value_next_worker_id" onchange="document.getElementById('chkRuleNextWorkerId').checked=(''==selectValue(this)?false:true);">
				<option value="0">- {$translate->_('common.nobody')} -</option>
				{foreach from=$workers item=worker}
					<option value="{$worker->id}" {if $crit_next_worker.value==$worker->id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td valign="top" colspan="2">
			{assign var=crit_groups value=$filter->criteria.groups}
			<label><input type="checkbox" id="chkRuleGroupId" name="rules[]" value="groups" onclick="toggleDiv('divRuleGroups',(this.checked?'block':'none'));" {if !is_null($crit_groups)}checked="checked"{/if}> Group/Bucket: (any of the following)</label><br>
			
			<div id="divRuleGroups" style="margin-left:20px;display:{if !is_null($crit_groups)}block{else}none{/if};">
			{foreach from=$groups key=group_id item=group}
			{if isset($memberships.$group_id)}
			<label><input type="checkbox" name="value_groups[]" value="{$group_id}" onclick="toggleDiv('divRuleGroup{$group_id}',(this.checked?'block':'none'));" {if isset($crit_groups.groups.$group_id)}checked="checked"{/if}> {$group->name}</label><br>
			<div id="divRuleGroup{$group_id}" style="display:{if isset($crit_groups.groups.$group_id)}block{else}none{/if};margin-left:15px;margin-bottom:5px;">
				<label><input type="checkbox" name="value_group{$group_id}_all" value="1" {if empty($crit_groups.groups.$group_id)}checked="checked"{/if} onclick="toggleDiv('divRuleGroupBuckets{$group_id}',(this.checked?'none':'block'));"> <i>{$translate->_('common.all')|capitalize}</i></label><br>
				<div id="divRuleGroupBuckets{$group_id}" style="display:{if empty($crit_groups.groups.$group_id)}none{else}block{/if};margin-left:15px;margin-bottom:5px;">
					<label><input type="checkbox" name="value_group{$group_id}_buckets[]" value="0" {if is_array($crit_groups.groups.$group_id) && in_array(0,$crit_groups.groups.$group_id)}checked="checked"{/if}> {$translate->_('common.inbox')|capitalize}</label><br>
					{foreach from=$group_buckets.$group_id item=bucket}
					<label><input type="checkbox" name="value_group{$group_id}_buckets[]" value="{$bucket->id}"  {if is_array($crit_groups.groups.$group_id) && in_array($bucket->id,$crit_groups.groups.$group_id)}checked="checked"{/if}> {$bucket->name}</label><br>
					{/foreach}
				</div>
			</div>
			{/if}
			{/foreach}
			</div>
		</td>
	</tr>
</table>

{* Get Ticket Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$ticket_fields filter=$filter divName="divGetTicketFields" label="Ticket custom fields"}

{* Message *}
{assign var=expanded value=false}
{if isset($filter->criteria.subject) || isset($filter->criteria.from) || isset($filter->criteria.body)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockMessage',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockMessage',false);"> <b>Message</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockMessage">
	<tr>
		<td valign="top">
			{assign var=crit_from value=$filter->criteria.from}
			<label><input type="checkbox" id="chkRuleFrom" name="rules[]" value="from" {if !is_null($crit_from)}checked="checked"{/if}> From:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_from" size="45" value="{$crit_from.value|escape}" onchange="document.getElementById('chkRuleFrom').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_subject value=$filter->criteria.subject}
			<label><input type="checkbox" id="chkfiltersubject" name="rules[]" value="subject" {if !is_null($crit_subject)}checked="checked"{/if}> Subject:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_subject" size="45" value="{$crit_subject.value|escape}" onchange="document.getElementById('chkfiltersubject').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_body value=$filter->criteria.body}
			<label><input type="checkbox" id="chkRuleBody" name="rules[]" value="body" {if !is_null($crit_body)}checked="checked"{/if}> Body Content:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_body" size="45" value="{$crit_body.value|escape}" onchange="document.getElementById('chkRuleBody').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			<i>Enter as a <a href="http://us2.php.net/manual/en/reference.pcre.pattern.syntax.php" target="_blank">regular expression</a>; scans content line-by-line.</i><br>
			Example: /(how do|where can)/i<br>
		</td>
	</tr>
</table>

{* Message Headers *}
{assign var=expanded value=false}
{section name=headers start=0 loop=5}
	{assign var=headerx value='header'|cat:$smarty.section.headers.iteration}
	{if isset($filter->criteria.$headerx)}
		{assign var=expanded value=true}
	{/if}
{/section}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockHeaders',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockHeaders',false);"> <b>Message headers</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockHeaders">
	{section name=headers start=0 loop=5}
	{assign var=headerx value='header'|cat:$smarty.section.headers.iteration}
	{assign var=crit_headerx value=$filter->criteria.$headerx}
	<tr>
		<td valign="top">
			<input type="checkbox" id="chkHeader{$smarty.section.headers.iteration}" name="rules[]" {if !is_null($crit_headerx)}checked="checked"{/if} value="header{$smarty.section.headers.iteration}">
			<input type="text" name="{$headerx}" value="{$crit_headerx.header|escape}" size="16" onchange="document.getElementById('chkHeader{$smarty.section.headers.iteration}').checked=((0==this.value.length)?false:true);">:
		</td>
		<td valign="top">
			<input type="text" name="value_{$headerx}" value="{$crit_headerx.value|escape}" size="45">
		</td>
	</tr>
	{/section}
</table>

{* Get Address Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$address_fields filter=$filter divName="divGetAddyFields" label="Sender address"}

{* Get Org Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$org_fields filter=$filter divName="divGetOrgFields" label="Sender organization"}

<br>
<h2>Then perform these actions:</h2>

<label><input type="checkbox" name="do[]" value="notify" {if !is_null($filter->actions.notify)}checked="checked"{/if}> <b>Send a worker notification</b></label><br>
{*
<blockquote style="margin-top:0px;" id="div_do_notity">
</blockquote>
*}

{assign var=act_email value=$filter->actions.email}
<label><input type="checkbox" name="do[]" value="email" {if !is_null($filter->actions.email)}checked="checked"{/if}> <b>Forward e-mail to:</b></label><br>
<blockquote style="margin-top:0px;" id="div_do_email">
	{foreach from=$addresses item=address}
	<label><input type="checkbox" name="do_email[]" value="{$address->address|escape}" {if is_array($act_email.to) && in_array($address->address,$act_email.to)}checked="checked"{/if} onclick="if(this.checked) $('#frmWatcherFilter input[name=do\[\]][value=email]').attr('checked','checked');"> {$address->address}</label><br>
	{/foreach}
</blockquote>

<br>

<button type="button" onclick="genericAjaxPanelPostCloseReloadView('frmWatcherFilter', '{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
</form>
<br>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"Add Watcher Filter");
	} );
</script>
