<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTabPreParser">
<input type="hidden" name="id" value="{$filter->id}">
<h2>Add Pre-Parser Filter</h2>

<div style="height:400;overflow:auto;">

<b>Filter Name:</b> (e.g. Spam Bounces)<br>
<input type="text" name="name" size="45" value="{$filter->name|escape}" style="width:95%;"><br>
<label><input type="checkbox" name="is_sticky" value="1" {if $filter->is_sticky}checked="checked"{/if}> <span style="border-bottom:1px dotted;" title="Sticky filters are checked for matches first, are manually sortable, and can be stacked with subsequent filters.">Sticky</span></label><br>
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
{if isset($filter->criteria.type) || isset($filter->criteria.from) || isset($filter->criteria.tocc) || isset($filter->criteria.body) || isset($filter->criteria.body_encoding) || isset($filter->criteria.attachment)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockMessage',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockMessage',false);"> <b>Message</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockMessage">
	<tr>
		<td valign="top">
			{assign var=crit_type value=$filter->criteria.type}
			<label><input type="checkbox" name="rules[]" value="type" id="chkRuleType" {if !is_null($crit_type)}checked="checked"{/if}> Is a:</label>
		</td>
		<td valign="top">
			<select name="value_type" onclick="document.getElementById('chkRuleType').checked=true;">
				<option value="new" {if $crit_type.value=='new'}selected="selected"{/if}>new message</option>
				<option value="reply" {if $crit_type.value=='reply'}selected="selected"{/if}>reply</option>
			</select>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_from value=$filter->criteria.from}
			<label><input type="checkbox" name="rules[]" value="from" id="chkRuleFrom" {if !is_null($crit_from)}checked="checked"{/if}> From:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_from" size="45" value="{$crit_from.value|escape}" onchange="document.getElementById('chkRuleFrom').checked=((0==this.value.length)?false:true);" style="width:95%;"><br>
			Example: customer@example.com, newsletter@*, *@spammer.com<br>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_tocc value=$filter->criteria.tocc}
			<label><input type="checkbox" id="chkRuleTo" name="rules[]" value="tocc" {if !is_null($crit_tocc)}checked="checked"{/if}> To/Cc:</label>
		</td>
		<td valign="top">
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
			<i>Enter as a <a href="http://us2.php.net/manual/en/regexp.reference.php" target="_blank">regular expression</a>; scans content line-by-line.</i><br>
			Example: /(how do|where can)/i<br>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_body_encoding value=$filter->criteria.body_encoding}
			<label><input type="checkbox" name="rules[]" value="body_encoding" id="chkRuleBodyEncoding" {if !is_null($crit_body_encoding)}checked="checked"{/if}> Body Charset:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_body_encoding" value="{$crit_body_encoding.value|escape}" size="45" onchange="document.getElementById('chkRuleBodyEncoding').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=crit_attachment value=$filter->criteria.attachment}
			<label><input type="checkbox" name="rules[]" value="attachment" id="chkRuleAttachment" {if !is_null($crit_attachment)}checked="checked"{/if}> Attachment Name:</label>
		</td>
		<td valign="top">
			<input type="text" name="value_attachment" value="{$crit_attachment.value|escape}" size="45" onchange="document.getElementById('chkRuleAttachment').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
</table>

{* Message Headers *}
{assign var=expanded value=false}
{if isset($filter->criteria.header)}
	{assign var=expanded value=true}
{/if}
<label><input type="checkbox" {if $expanded}checked="checked"{/if} onclick="toggleDiv('divBlockMessageHeaders',(this.checked?'block':'none'));if(!this.checked)checkAll('divBlockMessageHeaders',false);"> <b>Message Headers</b></label><br>
<table width="500" style="margin-left:10px;display:{if $expanded}block{else}none{/if};" id="divBlockMessageHeaders">
	<tr>
		<td colspan="2"><br><b>With message headers:</b> (use * for wildcards)</td>
	</tr>
	{section name=headers start=0 loop=5}
	{assign var=headerx value='header'|cat:$smarty.section.headers.iteration}
	{assign var=crit_headerx value=$filter->criteria.$headerx}
	<tr>
		<td valign="top">
			<input type="checkbox" name="rules[]" value="header{$smarty.section.headers.iteration}" id="chkRuleHeader{$smarty.section.headers.iteration}" {if !is_null($crit_headerx)}checked="checked"{/if}>
			<input type="text" name="{$headerx}" value="{$crit_headerx.header|escape}" size="16" onchange="document.getElementById('chkRuleHeader{$smarty.section.headers.iteration}').checked=((0==this.value.length)?false:true);">: 
		</td>
		<td valign="top">
			<input type="text" name="value_{$headerx}" value="{$crit_headerx.value|escape}" size="45" style="width:95%;">
		</td>
	</tr>
	{/section}
</table>

{* Get Address Fields *}
{include file="file:$core_tpl/groups/manage/filters/peek_get_custom_fields.tpl" fields=$address_fields filter=$filter divName="divGetAddyFields" label="Sender address"}

{* Get Org Fields *}
{include file="file:$core_tpl/groups/manage/filters/peek_get_custom_fields.tpl" fields=$org_fields filter=$filter divName="divGetOrgFields" label="Sender organization"}

<br>

<h2>Then perform these actions:</h2>
<table width="100%">
	<tr>
		<td valign="top">
			{assign var=act_blackhole value=$filter->actions.blackhole}
			<label><input type="radio" name="do[]" value="blackhole" {if empty($filter) || !is_null($act_blackhole)}checked="checked"{/if}> 
			Blackhole the message</label>
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=act_redirect value=$filter->actions.redirect}
			<label><input type="radio" name="do[]" value="redirect" {if !is_null($act_redirect)}checked="checked"{/if}> 
			Redirect to e-mail:</label> 
			<input type="text" name="do_redirect" size="45" value="{$act_redirect.to|escape}" style="width:300;">
		</td>
	</tr>
	<tr>
		<td valign="top">
			{assign var=act_bounce value=$filter->actions.bounce}
			<label><input type="radio" name="do[]" value="bounce" {if !is_null($act_bounce)}checked="checked"{/if}> 
			Bounce with message:</label>
			<div style="margin-left:30px;"><textarea rows="8" cols="80" name="do_bounce" style="width:95%;">{$act_bounce.message|escape}</textarea></div>
		</td>
	</tr>
</table>
</div>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>
</form>
<br>