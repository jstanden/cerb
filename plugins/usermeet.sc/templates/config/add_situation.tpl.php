{if !empty($situation_reason)}
	<h2>Modify Contact Situation</h2>
{else}
	<h2>Add a Contact Situation</h2>
{/if}
<input type="hidden" name="edit_reason" value="{$situation_reason|md5}">

<b>Reason for contacting:</b> (e.g. "I'd like more info on your products")<br>
<input type="text" name="reason" size="65" value="{$situation_reason}"><br>
<br>

<b>Deliver to:</b> (helpdesk e-mail address, blank for {$default_from})<br>
<input type="text" name="to" size="65" value="{$situation_params.to}"><br>
<br>

<b>Follow-up Questions:</b> (e.g. "Is there a specific product you're considering?")<br>
{foreach from=$situation_params.followups key=q item=long name=followups}
	<input type="text" name="followup[]" size="65" value="{$q}"> 
	<label><input type="checkbox" name="followup_long[]" value="{$smarty.foreach.followups.index}" {if $long}checked{/if}> Long Answer</label><br>
{/foreach}

{math assign=dispatch_start equation="x+1" x=$smarty.foreach.followups.index}
{section name="dispatch" start=$dispatch_start loop=100 max=5}
	<input type="text" name="followup[]" size="65" value=""> 
	<label><input type="checkbox" name="followup_long[]" value="{$smarty.section.dispatch.index}"> Long Answer</label><br>
{/section}
(save to add more follow-ups)<br>
<br>
