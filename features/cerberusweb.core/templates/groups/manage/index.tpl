<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmGroupEdit">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabMail">
<input type="hidden" name="group_id" value="{$group->id}">

<fieldset>
	<legend>Outgoing Mail Preferences</legend>
	
	<label><input type="checkbox" name="subject_has_mask" value="1" onclick="toggleDiv('divGroupCfgSubject',(this.checked)?'block':'none');" {if $group_settings.subject_has_mask}checked{/if}> Include custom prefix and mask in subject:</label><br>
	<blockquote id="divGroupCfgSubject" style="margin-left:20px;margin-bottom:0px;display:{if $group_settings.subject_has_mask}block{else}none{/if}">
		<b>Subject prefix:</b> (optional, e.g. "Billing", "Tech Support")<br>
		Re: [ <input type="text" name="subject_prefix" value="{$group_settings.subject_prefix}" size="24"> #MASK-12345-678]: This is the subject line<br>
	</blockquote>
</fieldset>

<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>

</form>
