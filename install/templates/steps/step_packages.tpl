<h2>Packages</h2>

<form action="index.php" method="POST">
<input type="hidden" name="step" value="{$smarty.const.STEP_PACKAGES}">
<input type="hidden" name="form_submit" value="1">

{if $failed}
<div class="error">
	Oops! Some required information was not provided.
</div>
{/if}

<h3>Environment</h3>

<div style="margin:5px 0px 0px 5px;">
	<div style="margin-bottom:5px;">
		<label>
			<input type="radio" name="package" value="standard" {if !$package || $package=="standard"}checked="checked"{/if}> <b>Production</b>
			<div style="margin-left:25px;">
				Cerb will be configured for real-world use.
			</div>
		</label>
	</div>
	
	<div style="margin-bottom:5px;">
		<label>
			<input type="radio" name="package" value="tutorial" {if $package=="tutorial"}checked="checked"{/if}> <b>Tutorial</b>
			<div style="margin-left:25px;">
				Cerb will be configured for evaluation, development, and testing.
			</div>
		</label>
	</div>
</div>

<h3>Optional Packages</h3>

<div style="margin:5px 0px 0px 5px;">
	<div style="margin-bottom:5px;">
		<label>
			<input type="checkbox" name="optional_packages[]" value="autoreply_bot"> <b>Auto-Reply Bot</b>
			<div style="margin-left:25px;">
				Automatically send a confirmation receipt back to the sender for each new conversation.
			</div>
		</label>
	</div>
	
	<div style="margin-bottom:5px;">
		<label>
			<input type="checkbox" name="optional_packages[]" value="chat_bot"> <b>Chat Bot</b>
			<div style="margin-left:25px;">
				A preconfigured bot with some helpful text-based conversational interactions.
			</div>
		</label>
	</div>
	
	<div style="margin-bottom:5px;">
		<label>
			<input type="checkbox" name="optional_packages[]" value="customer_satisfaction"> <b>Customer Satisfaction Surveys</b>
			<div style="margin-left:25px;">
				Gather and monitor customer satisfaction metrics like NPS, CSAT, and CES.
			</div>
		</label>
	</div>
	
	<div style="margin-bottom:5px;">
		<label>
			<input type="checkbox" name="optional_packages[]" value="reminder_bot"> <b>Reminder Bot</b>
			<div style="margin-left:25px;">
				A bot with conversational interactions for setting reminders from any record's card or profile page.
			</div>
		</label>
	</div>
</div>

<div style="margin-top:20px;">
	<button type="submit">Continue &raquo;</button>
</div>
</form>