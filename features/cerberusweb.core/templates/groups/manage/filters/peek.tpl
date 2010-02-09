<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmInboxFilter">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabInboxAdd">
<input type="hidden" name="group_id" value="{$group_id}">
<input type="hidden" name="id" value="{$filter->id}">
{if !empty($view_id)}<input type="hidden" name="view_id" value="{$view_id}">{/if}

<h2>Add Inbox Routing Rule</h2>

{if !empty($message_headers)}
<div id="" style="height:50px;padding:5px;overflow:auto;border:1px solid rgb(180,180,180);background-color:rgb(255,255,255);">
	{foreach from=$message_headers item=v key=k}
		<b>{$k|capitalize}</b>: {$v|escape|nl2br}<br>
	{/foreach}
</div>
<br>
{/if}

<b>Rule Name:</b> (e.g. Spam Bounces)<br>
<input type="text" name="name" value="{$filter->name|escape}" size="45" style="width:95%;"><br>
<label><input type="checkbox" name="is_sticky" value="1" onclick="toggleDiv('divStackable',this.checked?'inline':'none');" {if $filter->is_sticky}checked="checked"{/if}> <span style="border-bottom:1px dotted;" title="Sticky rules are checked for matches first, are manually sortable, and can be stacked with subsequent rules.">Sticky</span></label>
<span id="divStackable" style="display:{if $filter->is_sticky}inline{else}none{/if};"><label><input type="checkbox" name="is_stackable" value="1" {if $filter->is_stackable}checked="checked"{/if}> <span style="border-bottom:1px dotted;" title="Stackable rules combine their actions with those of subsequent matching rules.">Stackable</span></label></span>
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

{* Message *}
{assign var=expanded value=false}
{if isset($filter->criteria.subject) || isset($filter->criteria.from) || isset($filter->criteria.tocc) || isset($filter->criteria.body)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockMessage',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockMessage',false);"> <b>Message</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockMessage">
	<tr>
		<td>
			{assign var=crit_subject value=$filter->criteria.subject}
			<label><input type="checkbox" id="chkRuleSubject" name="rules[]" value="subject" {if !is_null($crit_subject)}checked="checked"{/if}> Subject:</label>
		</td>
		<td>
			<input type="text" name="value_subject" size="45" value="{$crit_subject.value|escape}" onchange="document.getElementById('chkRuleSubject').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td>
			{assign var=crit_from value=$filter->criteria.from}
			<label><input type="checkbox" id="chkRuleFrom" name="rules[]" value="from" {if !is_null($crit_from)}checked="checked"{/if}> From:</label>
		</td>
		<td>
			<input type="text" name="value_from" size="45" value="{$crit_from.value|escape}" onchange="document.getElementById('chkRuleFrom').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_tocc value=$filter->criteria.tocc}
			<label><input type="checkbox" id="chkRuleTo" name="rules[]" value="tocc" {if !is_null($crit_tocc)}checked="checked"{/if}> To/Cc:</label>
		</td>
		<td>
			<input type="text" name="value_tocc" size="45" value="{$crit_tocc.value|escape}" value="{$tocc_list}" onchange="document.getElementById('chkRuleTo').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			<i>Comma-delimited address patterns; only one e-mail must match.</i><br>
			Example: support@example.com, support@*, *@example.com<br>
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
		<td>
			<input type="checkbox" id="chkHeader{$smarty.section.headers.iteration}" name="rules[]" {if !is_null($crit_headerx)}checked="checked"{/if} value="header{$smarty.section.headers.iteration}">
			<input type="text" name="{$headerx}" value="{$crit_headerx.header|escape}" size="16" onchange="document.getElementById('chkHeader{$smarty.section.headers.iteration}').checked=((0==this.value.length)?false:true);">:
		</td>
		<td>
			<input type="text" name="value_{$headerx}" value="{$crit_headerx.value|escape}" size="45">
		</td>
	</tr>
	{/section}
</table>

{* Get Ticket Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$ticket_fields filter=$filter divName="divGetTicketFields" label="Ticket"}

{* Get Address Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$address_fields filter=$filter divName="divGetAddyFields" label="Sender address"}

{* Get Org Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$org_fields filter=$filter divName="divGetOrgFields" label="Sender organization"}

<br>
<h2>Then perform these actions:</h2>
<table width="500">
	<tr>
		{assign var=act_move value=$filter->actions.move}
		<td>
			<label><input id="chkDoMove" type="checkbox" name="do[]" value="move" {if isset($act_move)}checked="checked"{/if}> Move to:</label>
		</td>
		<td>
			<select name="do_move" onchange="document.getElementById('chkDoMove').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
	      		<optgroup label="Move to Group">
	      		{foreach from=$groups item=tm}
	      			{assign var=k value='t'|cat:$tm->id}
	      			<option value="{$k}" {if $tm->id==$act_move.group_id && 0==$act_move.bucket_id}selected="selected"{/if}>{$tm->name}</option>
	      		{/foreach}
	      		</optgroup>
	      		{foreach from=$team_categories item=categories key=teamId}
	      			{assign var=tm value=$groups.$teamId}
	      			<optgroup label="{$tm->name}">
	      			{foreach from=$categories item=category}
	      				{assign var=k value='c'|cat:$category->id}
	    				<option value="c{$category->id}" {if $category->id==$act_move.bucket_id}selected="selected"{/if}>{$category->name}</option>
	    			{/foreach}
	    			</optgroup>
	     		{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		{assign var=act_status value=$filter->actions.status}
		<td>
			<label><input id="chkDoStatus" type="checkbox" name="do[]" value="status" {if isset($act_status)}checked="checked"{/if}> Status:</label>
		</td>
		<td>
			<select name="do_status" onchange="document.getElementById('chkDoStatus').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
				<option value="0" {if isset($act_status) && !$act_status.is_closed && !$act_status.is_deleted}selected="selected"{/if}>{$translate->_('status.open')|capitalize}</option>
				<option value="3" {if isset($act_status) && !$act_status.is_closed && !$act_status.is_deleted && $act_status.is_waiting}selected="selected"{/if}>Waiting</option>
				{if $active_worker->hasPriv('core.ticket.actions.close') || (isset($act_status) && $act_status.is_closed && !$act_status.is_deleted)}
					<option value="1" {if isset($act_status) && $act_status.is_closed && !$act_status.is_deleted}selected="selected"{/if}>{$translate->_('status.closed')|capitalize}</option>
				{/if}
				{if $active_worker->hasPriv('core.ticket.actions.delete') || (isset($act_status) && $act_status.is_deleted)}
					<option value="2" {if isset($act_status) && $act_status.is_deleted}selected="selected"{/if}>Deleted</option>
				{/if}
			</select>
		</td>
	</tr>
	<tr>
		{assign var=act_spam value=$filter->actions.spam}
		<td>
			<label><input id="chkDoSpam" type="checkbox" name="do[]" value="spam" {if isset($act_spam)}checked="checked"{/if}> Spam:</label>
		</td>
		<td>
			<select name="do_spam" onchange="document.getElementById('chkDoSpam').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
				<option value="1" {if isset($act_spam) && $act_spam.is_spam}selected="selected"{/if}>{$translate->_('training.report_spam')|capitalize}</option>
				<option value="0" {if isset($act_spam) && !$act_spam.is_spam}selected="selected"{/if}>{$translate->_('training.not_spam')|capitalize}</option>
			</select>
		</td>
	</tr>
	<tr>
		{assign var=act_assign value=$filter->actions.assign}
		<td>
			<label><input id="chkDoAssign" type="checkbox" name="do[]" value="assign" {if isset($act_assign)}checked="checked"{/if}> Assign:</label>
		</td>
		<td>
			<select name="do_assign" onchange="document.getElementById('chkDoAssign').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
				{foreach from=$workers item=worker key=worker_id name=workers}
					{if $worker_id==$active_worker->id}{math assign=next_worker_id_sel equation="x" x=$smarty.foreach.workers.iteration}{/if}
					<option value="{$worker_id}" {if $act_assign.worker_id==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
				{/foreach}
			</select> 
	      	{if !empty($next_worker_id_sel)}
	      		<button type="button" onclick="this.form.do_assign.selectedIndex = {$next_worker_id_sel};">me</button>
	      	{/if}
		</td>
	</tr>
</table>

{* Set Ticket Fields *}
{include file="file:$core_tpl/internal/custom_fields/filters/peek_set_custom_fields.tpl" fields=$ticket_fields filter=$filter divName="divSetTicketFields" label="Set ticket custom fields"}

<br>

{if !empty($view_id)}
	<button type="button" onclick="ajax.postAndReloadView('frmInboxFilter','view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
{else}
	<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
{/if}
</form>
<br>