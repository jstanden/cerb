{include file="$path/tickets/teamwork/manage/menu.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamGeneral">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>'{$team->name}' Preferences</h2>
<br>

	<div style="margin-left:20px">
	<!-- <h3>Mail</h3>
	<br>
	 -->
	
	<b>E-mail Signature:</b><br>
	<div style="display:none">
		{assign var=default_signature value=$settings->get('default_signature')}
		{$default_signature}	
	</div>
	<textarea name="signature" rows="4" cols="76">{$team->signature}</textarea><br>
		E-mail Tokens: 
		<select name="" onchange="this.form.signature.value += this.options[this.selectedIndex].value;scrollElementToBottom(this.form.signature);this.selectedIndex=0;this.form.signature.focus();">
			<option value="">-- choose --</option>
			<optgroup label="Worker">
				<option value="#first_name#">#first_name#</option>
				<option value="#last_name#">#last_name#</option>
				<option value="#title#">#title#</option>
			</optgroup>
		</select>
	<br> 
	<br>
	</div>

<br>

<h2>'{$team->name}' Buckets</h2>
<br>

	<div style="margin-left:20px">
	{if !empty($categories)}
	<table cellspacing="2" cellpadding="0">
		<tr>
			<td><b>Bucket Name</b></td>
			<td><b>Access</b></td>
			<td><b>Remove</b></td>
		</tr>
		{foreach from=$categories item=cat key=cat_id name=cats}
			<tr>
				<td>
					<input type="hidden" name="ids[]" value="{$cat->id}">
					<input type="text" name="names[]" value="{$cat->name}" size="35">
				</td>
				<td>
					<select name="access[]">
						<option value="">Private</option>
						<option value="">Shared</option>
					</select>
				</td>
				<td align="center">
					<input type="checkbox" name="deletes[]" value="{$cat_id}">
				</td>
			</tr>
		{/foreach}
	</table>
	{else}
		<br>
		You haven't set up any buckets yet.  Buckets are containers which allow you to quickly organize the '{$team->name}' team workload.<br>
		<br>
		Example buckets:<br>
		<ul style="margin-top:0px;">
			<li>Receipts</li>
			<li>Newsletters</li>
			<li>Orders</li>
		</ul>
	{/if}
	<br>
	
	<h3>Add Buckets</h3>
	<b>Enter bucket names:</b> (one label per line)<br>
	<textarea rows="5" cols="45" name="add"></textarea><br>
	</div>
<br>

<h2>'{$team->name}' Members</h2>
<br>

	<div style="margin-left:20px">
	<table cellspacing="2" cellpadding="0">
		<tr>
			<td><b>Member</b></td>
			<td align="center"><b>Remove</b></td>
		</tr>
	
		{foreach from=$members item=member key=member_id name=members}
			<tr>
				<td>
					<input type="hidden" name="member_ids[]" value="{$member->id}">
					{$member->getName()}{if !empty($member->title)} ({$member->title}){/if}
				</td>
				<td align="center">
					<label><input type="checkbox" name="member_deletes[]" value="{$member->id}"></label>
				</td>
			</tr>
		{/foreach}
	</table>
	
	{if !empty($workers)}
	<br>
	<h3>Add Members</h3>
	<select name="member_adds[]" size="5" multiple="multiple">
		{foreach from=$workers item=worker name=workers}
			<option value="{$worker->id}">{$worker->getName()}{if !empty($worker->title)} ({$worker->title}){/if}</option>		
		{/foreach}
	</select><br>
	(Tip: Hold the <i>Control</i> or <i>Option</i> key to select multiple members)<br>
	{/if}
	</div>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
</div>
<br>
	
</form>