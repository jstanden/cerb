<div class="block" id="configMailPreparse">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTabPreParser">
<h2>Add Pre-Parser Filter</h2>

<b>Filter Name:</b><br>
<input type="text" name="name" size="45"> (e.g. Spam Bounces)<br>
<br>

<b>If incoming message:</b> (use * for wildcards)
<br>

<table>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="type" id="chkRuleType"> Is a:</label>
		</td>
		<td>
			<select name="value_type" onclick="document.getElementById('chkRuleType').checked=true;">
				<option value="new">new message</option>
				<option value="reply">reply</option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="from" id="chkRuleFrom"> From:</label>
		</td>
		<td>
			<input type="text" name="value_from" size="45" onchange="document.getElementById('chkRuleFrom').checked=((0==this.value.length)?false:true);">
		</td>
	</tr>
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="to" id="chkRuleTo"> To:</label>
		</td>
		<td>
			<select name="value_to" onclick="document.getElementById('chkRuleTo').checked=((0==this.value.length)?false:true);">
				{foreach from=$groups item=group key=group_id}
					<option value="{$group_id}">{$group->name|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{section name=headers start=0 loop=5}
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="header{$smarty.section.headers.iteration}" id="chkRuleHeader{$smarty.section.headers.iteration}"> Header:</label>
		</td>
		<td>
			<input type="text" name="header{$smarty.section.headers.iteration}" value="" size="16" onchange="document.getElementById('chkRuleHeader{$smarty.section.headers.iteration}').checked=((0==this.value.length)?false:true);">
			 =  
			<input type="text" name="value_header{$smarty.section.headers.iteration}" size="45">
		</td>
	</tr>
	{/section}
	{*
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="body" id="chkRuleContent"> Content:</label>
		</td>
		<td>
			<input type="text" name="value_body" size="45">
		</td>
	</tr>
	*}
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="attachment" id="chkRuleAttachment"> Attachment Name:</label>
		</td>
		<td>
			<input type="text" name="value_attachment" size="45" onchange="document.getElementById('chkRuleAttachment').checked=((0==this.value.length)?false:true);">
		</td>
	</tr>
</table>

<br>

<b>Then:</b><br>
<table>
	<tr>
		<td valign="top">
			<label><input type="radio" name="do[]" value="blackhole" checked> 
			Blackhole the message</label>
		</td>
	</tr>
	<tr>
		<td valign="top">
			<label><input type="radio" name="do[]" value="redirect"> 
			Redirect to e-mail:</label> 
			<input type="text" name="do_redirect" size="45" value="">
		</td>
	</tr>
	<tr>
		<td valign="top">
			<label><input type="radio" name="do[]" value="bounce"> 
			Bounce with message:</label>
			<div style="margin-left:30px;"><textarea rows="8" cols="80" name="do_bounce"></textarea></div>
		</td>
	</tr>
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')}</button>

</form>
</div>
<br>

{if !empty($filters)}
<div class="block" id="configMailPreparseFilters">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTabPreParseFilters">
<h2>Pre-Parser Filters</h2>

<table cellspacing="2" cellpadding="0">
	{counter start=0 print=false}
	{foreach from=$filters item=filter key=filter_id name=filters}
		<tr>
			<td>
				<label><input type="checkbox" name="deletes[]" value="{$filter_id}"> 
				<input type="hidden" name="ids[]" value="{$filter_id}">
				<b style='color:rgb(0,120,0);'>{$filter->name}</b></label><br>
				<blockquote style="margin:2px;margin-left:20px;">
					{foreach from=$filter->criteria item=crit key=crit_key}
						{if $crit_key=='type'}
							Is a <b>{$crit.value}</b> message<br>
						{elseif $crit_key=='from'}
							From = <b>{$crit.value}</b><br>
						{elseif $crit_key=='to'}
							{assign var=to_group_id value=$crit.value}
							To = <b>{$groups.$to_group_id->name}</b><br>
						{elseif $crit_key=='header1'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header2'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header3'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header4'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='header5'}
							Header <i>{$crit.header}</i> = <b>{$crit.value}</b><br>
						{elseif $crit_key=='body'}
							Body = <b>{$crit.value}</b><br>
						{elseif $crit_key=='attachment'}
							Attachment = <b>{$crit.value}</b><br>
						{/if}
					{/foreach}
					
					<blockquote style="margin:2px;margin-left:30px;font-size:90%;color:rgb(130,130,130);">
					{foreach from=$filter->actions item=action key=action_key}
						{if $action_key=="blackhole"}
							Blackhole<br>
						{elseif $action_key=="redirect"}
							Redirect to <b>{$action.to}</b><br>
						{elseif $action_key=="bounce"}
							Bounce<br>
						{/if}
					{/foreach}
					<span>(Matched {$filter->pos} incoming messages)</span><br>
					
					</blockquote>
				</blockquote>
			</td>
		</tr>
	{/foreach}
</table>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Delete Selected Rules</button>

</form>
</div>
<br>
{/if} {* endif filters exist *}
