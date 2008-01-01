{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doContactSend">
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td colspan="2">
      	<h1>What e-mail address should we reply to?</h1>
      	<input type="hidden" name="nature" value="{$sNature}">	
		<input name="from" value="{$last_from}" autocomplete="off" style="width:98%;"><br>
		<br>

		{if $allow_subjects}
      	<h1>Subject:</h1>
		<input name="subject" value="{$last_subject}" autocomplete="off" style="width:98%;"><br>
		<br>
		{/if}
		
      	{if !empty($situation_params.followups)}
      	<h1>{$situation}:</h1>
      	
      	<blockquote style="margin:20px;">
		{foreach from=$situation_params.followups key=question item=field_id name=situations}
			{math assign=idx equation="x-1" x=$smarty.foreach.situations.iteration}
	      	
	      	<h2>{$question}</h2>
	      	<input type="hidden" name="followup_q[]" value="{$question|escape}">
	      	{if !empty($field_id)}
	      		{assign var=field value=$ticket_fields.$field_id}

				<input type="hidden" name="field_ids[]" value="{$field_id|escape}">
	      		
	      		{if $field->type=='S'}
	      			<input name="followup_a[]" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;">
	      		{elseif $field->type=='T'}
	      			<textarea name="followup_a[]" rows="5" cols="60" style="width:98%;">{$last_followup_a.$idx|escape}</textarea>
	      		{elseif $field->type=='D'}
	      			<select name="followup_a[]">
	      				<option value=""></option>
	      				{foreach from=$field->options item=opt}
	      				<option value="{$opt}" {if $last_followup_a.$idx==$opt}selected{/if}>{$opt}
	      				{/foreach}
	      			</select>
	      		{elseif $field->type=='E'}
	      			<input name="followup_a[]" value="{$last_followup_a.$idx|escape}" autocomplete="off"><br>
	      		{elseif $field->type=='C'}
	      			<label><input name="followup_a[]" type="checkbox" value="1" {if $last_followup_a.$idx}checked{/if}> Yes</label>
	      		{/if}
	      		
	      	{else}
	      		<input type="hidden" name="field_ids[]" value="0">
				<input name="followup_a[]" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;">
			{/if}
			<br>
			<br>
		{/foreach}
		</blockquote>
		{/if}
		
      	<h1>Message:</h1>	
		<textarea name="content" rows="10" cols="60" style="width:98%;">{$last_content}</textarea><br>
		<br>
		
		{if $captcha_enabled}
	      	<h1>Please type the text from the image below:</h1>	
			<b>Text:</b> <input name="captcha" class="question" value="" size="10" autocomplete="off"><br>
			<img src="{devblocks_url}c=captcha{/devblocks_url}"><br>
			<br>
		{/if}
		
		<br>
		<b>Logged IP:</b> {$fingerprint.ip}<br>
		<br>
		
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> Send Message</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"> Discard</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
<br>
