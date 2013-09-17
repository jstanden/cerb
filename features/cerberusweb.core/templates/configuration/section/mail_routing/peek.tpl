<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_routing">
<input type="hidden" name="action" value="saveMailRoutingRuleAdd">
<input type="hidden" name="id" value="{$rule->id}">

<b>Rule Name:</b> (e.g. ProductX Support)<br>
<input type="text" name="name" value="{$rule->name}" size="45" style="width:95%;"><br>
<label><input type="checkbox" name="is_sticky" value="1" {if $rule->is_sticky}checked="checked"{/if}> <span style="border-bottom:1px dotted;" title="Sticky rules are checked for matches first, are manually sortable, and can be stacked with subsequent rules.">Sticky</span></label>
<br>
<br>

<h2>If these criteria match:</h2>

{* Date/Time *}
{assign var=expanded value=false}
{if isset($rule->criteria.dayofweek) || isset($rule->criteria.timeofday)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockDateTime',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockDateTime',false);"> <b>Current Date/Time</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockDateTime">
	<tr>
		<td valign="top">
			{assign var=crit_dayofweek value=$rule->criteria.dayofweek}
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
			{assign var=crit_timeofday value=$rule->criteria.timeofday}
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
{if isset($rule->criteria.subject) || isset($rule->criteria.from) || isset($rule->criteria.tocc) || isset($rule->criteria.body)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockMessage',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockMessage',false);"> <b>Message</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockMessage">
	<tr>
		<td valign="top">
			{assign var=crit_tocc value=$rule->criteria.tocc}
			<label><input type="checkbox" id="chkRuleTo" name="rules[]" value="tocc" {if !is_null($crit_tocc)}checked="checked"{/if}> To/Cc:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_tocc" size="45" value="{$crit_tocc.value}" value="{$tocc_list}" onchange="document.getElementById('chkRuleTo').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			<i>Comma-delimited address patterns; only one e-mail must match.</i><br>
			Example: support@example.com, support@*, *@example.com<br>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_from value=$rule->criteria.from}
			<label><input type="checkbox" id="chkRuleFrom" name="rules[]" value="from" {if !is_null($crit_from)}checked="checked"{/if}> From:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_from" size="45" value="{$crit_from.value}" onchange="document.getElementById('chkRuleFrom').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_subject value=$rule->criteria.subject}
			<label><input type="checkbox" id="chkRuleSubject" name="rules[]" value="subject" {if !is_null($crit_subject)}checked="checked"{/if}> Subject:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_subject" size="45" value="{$crit_subject.value}" onchange="document.getElementById('chkRuleSubject').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_body value=$rule->criteria.body}
			<label><input type="checkbox" id="chkRuleBody" name="rules[]" value="body" {if !is_null($crit_body)}checked="checked"{/if}> Body Content:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_body" size="45" value="{$crit_body.value}" onchange="document.getElementById('chkRuleBody').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			<i>Enter as a <a href="http://us2.php.net/manual/en/reference.pcre.pattern.syntax.php" target="_blank">regular expression</a>; scans content line-by-line.</i><br>
			Example: /(how do|where can)/i<br>
		</td>
	</tr>
</table>

{* Message Headers *}
{assign var=expanded value=false}
{section name=headers start=0 loop=5}
	{assign var=headerx value='header'|cat:$smarty.section.headers.iteration}
	{if isset($rule->criteria.$headerx)}
		{assign var=expanded value=true}
	{/if}
{/section}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockHeaders',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockHeaders',false);"> <b>Message headers</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockHeaders">
	{section name=headers start=0 loop=5}
	{assign var=headerx value='header'|cat:$smarty.section.headers.iteration}
	{assign var=crit_headerx value=$rule->criteria.$headerx}
	<tr>
		<td valign="top">
			<input type="checkbox" id="chkHeader{$smarty.section.headers.iteration}" name="rules[]" {if !is_null($crit_headerx)}checked="checked"{/if} value="header{$smarty.section.headers.iteration}">
			<input type="text" name="{$headerx}" value="{$crit_headerx.header}" size="16" onchange="document.getElementById('chkHeader{$smarty.section.headers.iteration}').checked=((0==this.value.length)?false:true);">:
		</td>
		<td valign="top">
			<input type="text" name="value_{$headerx}" value="{$crit_headerx.value}" size="45">
		</td>
	</tr>
	{/section}
</table>

{* Get Address Fields *}
{include file="devblocks:cerberusweb.core::internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$address_fields filter=$rule divName="divGetAddyFields" label="Sender address"}

{* Get Org Fields *}
{include file="devblocks:cerberusweb.core::internal/custom_fields/filters/peek_get_custom_fields.tpl" fields=$org_fields filter=$rule divName="divGetOrgFields" label="Sender organization"}

<br>
<h2>Then perform these actions:</h2>
<table width="500">
	<tr>
		{assign var=act_move value=$rule->actions.move}
		<td valign="top">
			<label><input id="chkDoMove" type="checkbox" name="do[]" value="move" {if isset($act_move)}checked="checked"{/if}> Move to Inbox:</label>
		</td>
		<td valign="top">
			<select name="do_move" onchange="document.getElementById('chkDoMove').checked=((''==selectValue(this))?false:true);">
				<option value="">&nbsp;</option>
	      		{foreach from=$groups item=tm}
	      			{assign var=k value='t'|cat:$tm->id}
	      			<option value="{$k}" {if $tm->id==$act_move.group_id}selected="selected"{/if}>{$tm->name}</option>
	      		{/foreach}
			</select>
		</td>
	</tr>
</table>

{* Set Ticket Fields *}
{include file="devblocks:cerberusweb.core::internal/custom_fields/filters/peek_set_custom_fields.tpl" fields=$ticket_fields filter=$rule divName="divSetTicketFields" label="Set ticket custom fields"}

<br>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', 'Add Mail Routing Rule');
	} );
</script>
