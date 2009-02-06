<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTabPreParser">
<input type="hidden" name="id" value="{$filter->id}">
<h2>Add Pre-Parser Filter</h2>

<div style="height:400;overflow:auto;">
<table width="100%">
	<tr>
		<td colspan="2">
			<b>Filter Name:</b> (e.g. Spam Bounces)<br>
			<input type="text" name="name" size="45" value="{$filter->name|escape}" style="width:95%;"><br>
			<br>
		</td>
	</tr>

	<tr>
		<td colspan="2">
			<b>If incoming message:</b> (use * for wildcards)
		</td>
	</tr>

	<tr>
		<td>
			{assign var=crit_type value=$filter->criteria.type}
			<label><input type="checkbox" name="rules[]" value="type" id="chkRuleType" {if !is_null($crit_type)}checked="checked"{/if}> Is a:</label>
		</td>
		<td>
			<select name="value_type" onclick="document.getElementById('chkRuleType').checked=true;">
				<option value="new" {if $crit_type.value=='new'}selected="selected"{/if}>new message</option>
				<option value="reply" {if $crit_type.value=='reply'}selected="selected"{/if}>reply</option>
			</select>
		</td>
	</tr>
	<tr>
		<td>
			{assign var=crit_from value=$filter->criteria.from}
			<label><input type="checkbox" name="rules[]" value="from" id="chkRuleFrom" {if !is_null($crit_from)}checked="checked"{/if}> From:</label>
		</td>
		<td>
			<input type="text" name="value_from" size="45" value="{$crit_from.value|escape}" onchange="document.getElementById('chkRuleFrom').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td>
			{assign var=crit_to value=$filter->criteria.to}
			<label><input type="checkbox" name="rules[]" value="to" id="chkRuleTo" {if !is_null($crit_to)}checked="checked"{/if}> To:</label>
		</td>
		<td>
			<select name="value_to" onclick="document.getElementById('chkRuleTo').checked=((0==this.value.length)?false:true);">
				{foreach from=$groups item=group key=group_id}
					<option value="{$group_id}" {if $crit_to.value==$group_id}selected="selected"{/if}>{$group->name|escape}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	{*
	<tr>
		<td>
			<label><input type="checkbox" name="rules[]" value="body" id="chkRuleContent"> Content:</label>
		</td>
		<td>
			<input type="text" name="value_body" size="45" style="width:95%;">
		</td>
	</tr>
	*}
	<tr>
		<td>
			{assign var=crit_body_encoding value=$filter->criteria.body_encoding}
			<label><input type="checkbox" name="rules[]" value="body_encoding" id="chkRuleBodyEncoding" {if !is_null($crit_body_encoding)}checked="checked"{/if}> Body Charset:</label>
		</td>
		<td>
			<input type="text" name="value_body_encoding" value="{$crit_body_encoding.value|escape}" size="45" onchange="document.getElementById('chkRuleBodyEncoding').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
	<tr>
		<td>
			{assign var=crit_attachment value=$filter->criteria.attachment}
			<label><input type="checkbox" name="rules[]" value="attachment" id="chkRuleAttachment" {if !is_null($crit_attachment)}checked="checked"{/if}> Attachment Name:</label>
		</td>
		<td>
			<input type="text" name="value_attachment" value="{$crit_attachment.value|escape}" size="45" onchange="document.getElementById('chkRuleAttachment').checked=((0==this.value.length)?false:true);" style="width:95%;">
		</td>
	</tr>
</table>

<table width="100%">	
	<tr>
		<td colspan="2"><br><b>With message headers:</b> (use * for wildcards)</td>
	</tr>
	{section name=headers start=0 loop=5}
	{assign var=headerx value='header'|cat:$smarty.section.headers.iteration}
	{assign var=crit_headerx value=$filter->criteria.$headerx}
	<tr>
		<td>
			<input type="checkbox" name="rules[]" value="header{$smarty.section.headers.iteration}" id="chkRuleHeader{$smarty.section.headers.iteration}" {if !is_null($crit_headerx)}checked="checked"{/if}>
			<input type="text" name="{$headerx}" value="{$crit_headerx.header|escape}" size="16" onchange="document.getElementById('chkRuleHeader{$smarty.section.headers.iteration}').checked=((0==this.value.length)?false:true);">: 
		</td>
		<td>
			<input type="text" name="value_{$headerx}" value="{$crit_headerx.value|escape}" size="45" style="width:95%;">
		</td>
	</tr>
	{/section}
</table>

<br>

<b>Then:</b><br>
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