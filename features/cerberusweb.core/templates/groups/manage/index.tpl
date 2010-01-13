<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmGroupEdit">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabMail">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>Preferences</h2>
<br>
	<div style="margin-left:20px">
	<h3>Anti-Spam</h3>
	
	When new messages have spam probability 
	<select name="spam_threshold">
		<option value="80" {if $group_spam_threshold=="80"}selected{/if}>80%</option>
		<option value="85" {if $group_spam_threshold=="85"}selected{/if}>85%</option>
		<option value="90" {if $group_spam_threshold=="90"}selected{/if}>90%</option>
		<option value="95" {if $group_spam_threshold=="95"}selected{/if}>95%</option>
		<option value="99" {if $group_spam_threshold=="99"}selected{/if}>99%</option>
	</select>
	 or higher:<br>
	<blockquote style="margin-top:0px;">
		<label><input type="radio" name="spam_action" value="0" {if $group_spam_action==0}checked{/if}> Do nothing</label><br>
		<label><input type="radio" name="spam_action" value="1" {if $group_spam_action==1}checked{/if}> Delete</label><br>
		{if !empty($categories)}
		<label><input type="radio" name="spam_action" value="2" {if $group_spam_action==2}checked{/if}> Move to bucket for review: </label>
		<select name="spam_action_moveto" onclick="this.form.spam_action[2].checked=true;">
			{foreach from=$categories item=bucket key=bucket_id}
				<option value="{$bucket_id}" {if $group_spam_action_param==$bucket_id}selected{/if}>{$bucket->name}</option>
			{/foreach}
		</select>
		{/if}
	</blockquote>
	
	<div class="subtle2" style="margin:0px;">
	<h3>Group E-mail Preferences</h3>

	<b>Send replies as e-mail:</b> (optional, defaults to: {$settings->get('cerberusweb.core','default_reply_from','')})<br>
	<input type="text" name="sender_address" value="{$group_settings.reply_from|escape}" size="65"><br>
	<span style="color:rgb(30,150,30);">(Make sure the above address delivers to the helpdesk or you won't receive replies!)</span><br>
	<br>
	
	<b>Send replies as name:</b> (optional, defaults to: {$settings->get('cerberusweb.core','default_reply_personal','')})<br>
	<input type="text" name="sender_personal" value="{$group_settings.reply_personal|escape}" size="65"><br>
	<label><input type="checkbox" name="sender_personal_with_worker" value="1" {if !empty($group_settings.reply_personal_with_worker)}checked{/if}> Also prefix the replying worker's name as the sender.</label><br>
	<br>
	
	<label><input type="checkbox" name="subject_has_mask" value="1" onclick="toggleDiv('divGroupCfgSubject',(this.checked)?'block':'none');" {if $group_settings.subject_has_mask}checked{/if}> Include the ticket's ID in subject line:</label><br>
	<blockquote id="divGroupCfgSubject" style="margin-left:20px;margin-bottom:0px;display:{if $group_settings.subject_has_mask}block{else}none{/if}">
		<b>Subject prefix:</b> (optional, e.g. "Billing", "Tech Support")<br>
		Re: [ <input type="text" name="subject_prefix" value="{$group_settings.subject_prefix|escape}" size="24"> #MASK-12345-678]: This is the subject line<br>
	</blockquote>
	<br>
	
	<b>Group E-mail Signature:</b> (optional, defaults to helpdesk signature)<br>
	<div style="display:none">
		{assign var=default_signature value=$settings->get('cerberusweb.core','default_signature')}
		<textarea name="default_signature">{$default_signature}</textarea>	
	</div>
	<textarea name="signature" rows="4" cols="76">{$team->signature}</textarea><br>
		E-mail Tokens: 
		<select name="sig_token" onchange="insertAtCursor(this.form.signature,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.signature.focus();">
			<option value="">-- choose --</option>
			<optgroup label="Worker">
				<option value="#first_name#">First Name</option>
				<option value="#last_name#">Last Name</option>
				<option value="#title#">Title</option>
			</optgroup>
		</select>
		
		{if !empty($default_signature)}
		<button type="button" onclick="this.form.signature.value=this.form.default_signature.value;">set to default</button>
		{/if}
	<br> 
	</div>
	<br>
	
	<h3>New Ticket Auto-Response</h3>
	
	<label><input type="checkbox" name="auto_reply_enabled" value="1" onclick="toggleDiv('divGroupCfgAutoReply',(this.checked)?'block':'none');" {if $group_settings.auto_reply_enabled}checked{/if}> <b>Send an auto-response when this group receives a new message?</b></label><br>
	<div style="margin-top:10px;margin-left:20px;display:{if $group_settings.auto_reply_enabled}block{else}none{/if};" id="divGroupCfgAutoReply">
		<b>Send the following message:</b><br>
		<textarea name="auto_reply" rows="10" cols="76">{$group_settings.auto_reply}</textarea><br>
			<b>E-mail Tokens:</b>
			
			<select name="autoreply_token" onchange="insertAtCursor(this.form.auto_reply,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.auto_reply.focus();">
				<option value="">-- choose --</option>
				<optgroup label="General">
					<option value="#timestamp#">Current Time</option>
				</optgroup>
				<optgroup label="First Requester">
					<option value="#sender#">E-mail</option>
					<option value="#sender_first#">First Name</option>
				</optgroup>
				<optgroup label="First Message">
					<option value="#orig_body#">Message Body</option>
				</optgroup>
				<optgroup label="Ticket">
					<option value="#mask#">Reference ID</option>
					<option value="#ticket_id#">Internal ID</option>
					<option value="#subject#">Subject</option>
					<!-- 
					<option value="#group#">Group Name</option>
					<option value="#bucket#">Bucket Name</option>
					 -->
				</optgroup>
			</select>
		<br>
	</div> 
	<br>
	
	<h3>Close Ticket Auto-Response</h3>
	
	<label><input type="checkbox" name="close_reply_enabled" value="1" onclick="toggleDiv('divGroupCfgCloseReply',(this.checked)?'block':'none');" {if $group_settings.close_reply_enabled}checked{/if}> <b>Send an auto-response when a ticket in this group is closed?</b></label><br>
	<div style="margin-top:10px;margin-left:20px;display:{if $group_settings.close_reply_enabled}block{else}none{/if};" id="divGroupCfgCloseReply">
		<b>Send the following message:</b><br>
		<textarea name="close_reply" rows="10" cols="76">{$group_settings.close_reply}</textarea><br>
			E-mail Tokens: 
			<select name="closereply_token" onchange="insertAtCursor(this.form.close_reply,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.close_reply.focus();">
				<option value="">-- choose --</option>
				<optgroup label="General">
					<option value="#timestamp#">Current Time</option>
				</optgroup>
				<optgroup label="First Requester">
					<option value="#sender#">E-mail</option>
					<option value="#sender_first#">First Name</option>
				</optgroup>
				<optgroup label="First Message">
					<option value="#orig_body#">Message Body</option>
				</optgroup>
				<optgroup label="Ticket">
					<option value="#mask#">Reference ID</option>
					<option value="#ticket_id#">Internal ID</option>
					<option value="#subject#">Subject</option>
				</optgroup>
			</select>
		<br>
	</div> 
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	
	</div>
</div>

</form>