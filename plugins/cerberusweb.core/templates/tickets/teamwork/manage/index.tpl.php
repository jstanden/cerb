{include file="$path/tickets/teamwork/manage/menu.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveTeamGeneral">
<input type="hidden" name="team_id" value="{$team->id}">

<div class="block">
<h2>Preferences</h2>
<br>

	<div style="margin-left:20px">
	<!-- <h3>Mail</h3>
	<br>
	 -->
	
	<h3>Anti-Spam</h3>
	
	When new messages have spam probability 
	<select name="spam_threshold">
		<option value="80" selected>80%</option>
		<option value="85">85%</option>
		<option value="90">90%</option>
		<option value="95">95%</option>
		<option value="99">99%</option>
	</select>
	 or higher:<br>
	<blockquote style="margin-top:0px;">
		<label><input type="radio" name="spam_action" value="0" checked> Do nothing</label><br>
		<label><input type="radio" name="spam_action" value="1"> Delete</label><br>
		<label><input type="radio" name="spam_action" value="2"> Move to bucket for review: </label>
		<select name="spam_action_moveto" onclick="this.form.spam_action[2].checked=true;">
			{foreach from=$categories item=bucket key=bucket_id}
				<option value="c{$bucket_id}">{$bucket->name}</option>
			{/foreach}
		</select>
	</blockquote>
	
	<h3>E-mail</h3>
	
	<b>Team E-mail Signature:</b><br>
	<div style="display:none">
		{assign var=default_signature value=$settings->get('default_signature')}
		<textarea name="default_signature">{$default_signature}</textarea>	
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
		
		{if !empty($default_signature)}
		<button type="button" onclick="this.form.signature.value=this.form.default_signature.value;">set to default</button>
		{/if}
	<br> 
	<br>
	
	<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
	
	</div>
</div>

</form>