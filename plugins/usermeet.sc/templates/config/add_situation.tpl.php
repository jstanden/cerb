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

<b>Follow-up Questions:</b> (e.g. "Which product are you considering?") -- optionally save to ticket field<br>
{foreach from=$situation_params.followups key=q item=field_id name=followups}
	<input type="text" name="followup[]" size="65" value="{$q}"> 
	<!-- <label><input type="checkbox" name="followup_long[]" value="{$smarty.foreach.followups.index}" {if $long}checked{/if}> Long Answer</label><br>-->
	<select name="followup_fields[]">
		<option value="">-- append to message --</option>
		{foreach from=$ticket_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		<option value="{$f_id}" {if $f_id==$field_id}selected{/if}>{$groups.$field_group_id->name}: {$f->name|escape}</option>
		{/foreach}
	</select>
	<br>
{/foreach}

{math assign=dispatch_start equation="x+1" x=$smarty.foreach.followups.index}
{section name="dispatch" start=$dispatch_start loop=100 max=5}
	<input type="text" name="followup[]" size="65" value=""> 
	<!-- <label><input type="checkbox" name="followup_long[]" value="{$smarty.section.dispatch.index}"> Long Answer</label><br> -->
	<select name="followup_fields[]">
		<option value="">-- append to message --</option>
		{foreach from=$ticket_fields item=f key=f_id}
		{assign var=field_group_id value=$f->group_id}
		<option value="{$f_id}">{$groups.$field_group_id->name}: {$f->name|escape}</option>
		{/foreach}
	</select>
	<br>
{/section}
(save to add more follow-ups)<br>
<br>

