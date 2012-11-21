<form action="{devblocks_url}{/devblocks_url}" method="POST" autocomplete="off">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveSecurityTab">

<div class="help-box">
	<h1>Set up your secret questions</h1>
	
	<p>
		When recovering your account's login information, you'll be asked one or more of the following secret questions to verify your identity.
	</p>
	
	<p>
		This helps protect you in the event that your email account is compromised, because the attacker will still need to be able to answer personal questions about you before they can reset your login information.  Without these secret questions, your account can be reset with nothing more than a confirmation code that is emailed to you.
	</p>
	<p>
		You should pick questions that don't have answers that could be easily obtained from social networks or a Google search.  Your answers shouldn't be continuous numbers or come from a small set of choices that could be guessed in a few attempts, such as "How old were you when..." or "How many...".
	</p>
</div>

{$q_placeholder = ["e.g. Where do you wish you met your spouse?","e.g. What is your favorite sentence in your favorite book?","e.g. What did you turn into gold during a lucid dream?"]}
{$a_placeholder = ["astronaut training","\"Did I say sharks?\" I exclaimed hastily. \"I meant 150 pearls. Sharks wouldn't make sense.\"","a rubber duck"]}

{section start=0 loop=3 name=secrets}
{$section_idx = $smarty.section.secrets.index}
<fieldset>
	<legend>Secret Question #{$smarty.section.secrets.iteration}</legend>

	<table cellspacing="1" cellpadding="0" border="0">
		<tr>
			<td>Question:</td>
			<td><input type="text" name="sq_q[]" value="{$secret_questions.$section_idx.q}" size="96" placeholder="{$q_placeholder.$section_idx}" autocomplete="off"></td>
		</tr>
		<tr>
			<td>Hint:</td>
			<td><input type="text" name="sq_h[]" value="{$secret_questions.$section_idx.h}" size="96" placeholder="" autocomplete="off"></td>
		</tr>
		<tr>
			<td>Answer:</td>
			<td><input type="text" name="sq_a[]" value="{$secret_questions.$section_idx.a}" size="96" placeholder="{$a_placeholder.$section_idx}" autocomplete="off"></td>
		</tr>
	</table>
</fieldset>
{/section}

{if $auth_extension instanceof Extension_LoginAuthenticator}
	{$auth_extension->renderWorkerPrefs($worker)}
{/if}

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>