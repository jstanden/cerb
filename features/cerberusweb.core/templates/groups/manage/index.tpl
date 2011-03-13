<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmGroupEdit">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabMail">
<input type="hidden" name="team_id" value="{$group->id}">

<fieldset>
	<legend>Outgoing Mail Preferences</legend>
	
	<label><input type="checkbox" name="sender_personal_with_worker" value="1" {if !empty($group_settings.reply_personal_with_worker)}checked{/if}> <strike>Also prefix the replying worker's name as the sender.</strike></label><br>
	
	<label><input type="checkbox" name="subject_has_mask" value="1" onclick="toggleDiv('divGroupCfgSubject',(this.checked)?'block':'none');" {if $group_settings.subject_has_mask}checked{/if}> Include the ticket's ID in subject line:</label><br>
	<blockquote id="divGroupCfgSubject" style="margin-left:20px;margin-bottom:0px;display:{if $group_settings.subject_has_mask}block{else}none{/if}">
		<b>Subject prefix:</b> (optional, e.g. "Billing", "Tech Support")<br>
		Re: [ <input type="text" name="subject_prefix" value="{$group_settings.subject_prefix}" size="24"> #MASK-12345-678]: This is the subject line<br>
	</blockquote>
</fieldset>

<fieldset>
	<legend>Anti-Spam</legend>
	
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
</fieldset>

<fieldset>
	<legend>New Ticket Auto-Response</legend>
	
	<label><input type="checkbox" name="auto_reply_enabled" value="1" onclick="toggleDiv('divGroupCfgAutoReply',(this.checked)?'block':'none');" {if $group_settings.auto_reply_enabled}checked{/if}> <b>Send an auto-response when this group receives a new message?</b></label><br>
	<div style="margin-top:10px;margin-left:20px;display:{if $group_settings.auto_reply_enabled}block{else}none{/if};" id="divGroupCfgAutoReply">
		<b>Send the following message:</b><br>
		<textarea name="auto_reply" rows="10" cols="76">{$group_settings.auto_reply}</textarea><br>
			<button type="button" onclick="genericAjaxPost('frmGroupEdit','divSnippetAutoReplyTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.ticket&snippet_field=auto_reply');"><span class="cerb-sprite sprite-gear"></span> Test</button>
			<select name="autoreply_token" onchange="insertAtCursor(this.form.auto_reply,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.auto_reply.focus();">
				<option value="">-- insert at cursor --</option>
				{foreach from=$ticket_token_labels key=k item=v}
				<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
				{/foreach}
			</select>
		<br>
		<div id="divSnippetAutoReplyTester"></div>
	</div> 
</fieldset>

<fieldset>
	<legend>Close Ticket Auto-Response</legend>
	
	<label><input type="checkbox" name="close_reply_enabled" value="1" onclick="toggleDiv('divGroupCfgCloseReply',(this.checked)?'block':'none');" {if $group_settings.close_reply_enabled}checked{/if}> <b>Send an auto-response when a ticket in this group is closed?</b></label><br>
	<div style="margin-top:10px;margin-left:20px;display:{if $group_settings.close_reply_enabled}block{else}none{/if};" id="divGroupCfgCloseReply">
		<b>Send the following message:</b><br>
		<textarea name="close_reply" rows="10" cols="76">{$group_settings.close_reply}</textarea><br>
			<button type="button" onclick="genericAjaxPost('frmGroupEdit','divSnippetCloseReplyTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.ticket&snippet_field=close_reply');"><span class="cerb-sprite sprite-gear"></span> Test</button>
			<select name="closereply_token" onchange="insertAtCursor(this.form.close_reply,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.close_reply.focus();">
				<option value="">-- insert at cursor --</option>
				{foreach from=$ticket_token_labels key=k item=v}
				<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
				{/foreach}
			</select>
		<br>
		<div id="divSnippetCloseReplyTester"></div>
	</div> 
</fieldset>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>

</form>
