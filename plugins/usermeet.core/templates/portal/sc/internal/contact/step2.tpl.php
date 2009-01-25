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
		<div style="border-bottom:1px solid rgb(180,180,180);margin-bottom:10px;">
		<h1 style="margin-bottom:0px;">{$translate->_('portal.common.open_ticket')}</h1>
		</div>
      
      	<h2>{$translate->_('portal.public.what_email_reply')}</h2>
      	<input type="hidden" name="nature" value="{$sNature}">	
		<input name="from" value="{$last_from|escape}" autocomplete="off" style="width:98%;"><br>
		<br>

      	<h2>{$translate->_('ticket.subject')|capitalize}:</h2>
      	{if $allow_subjects}
		<input type="text" name="subject" value="{if !empty($last_subject)}{$last_subject|escape}{else}{$situation|escape}{/if}" autocomplete="off" style="width:98%;"><br>
		{else}
		{$situation|escape}<br>
		{/if}
		<br>
		
      	{if !empty($situation_params.followups)}
      	<h2>Additional Information:</h2>
      	
      	<blockquote style="margin:20px;">
		{foreach from=$situation_params.followups key=question item=field_id name=situations}
			{math assign=idx equation="x-1" x=$smarty.foreach.situations.iteration}
	      	
	      	<h2>{$question}</h2>
	      	<input type="hidden" name="followup_q[]" value="{$question|escape}">
	      	{if !empty($field_id)}
	      		{assign var=field value=$ticket_fields.$field_id}

				<input type="hidden" name="field_ids[]" value="{$field_id|escape}">
	      		
	      		{if $field->type=='S'}
	      			<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;">
	      		{elseif $field->type=='N'}
	      			<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;">
	      		{elseif $field->type=='T'}
	      			<textarea name="followup_a_{$idx}" rows="5" cols="60" style="width:98%;">{$last_followup_a.$idx|escape}</textarea>
	      		{elseif $field->type=='D'}
	      			<select name="followup_a_{$idx}">
	      				<option value=""></option>
	      				{foreach from=$field->options item=opt}
	      				<option value="{$opt}" {if $last_followup_a.$idx==$opt}selected{/if}>{$opt}
	      				{/foreach}
	      			</select>
	      		{elseif $field->type=='E'}
	      			<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off"><br>
	      		{elseif $field->type=='C'}
	      			<label><input name="followup_a_{$idx}" type="checkbox" value="Yes" {if $last_followup_a.$idx}checked{/if}> {$translate->_('common.yes')|capitalize}</label>
	      		{/if}
	      		
	      	{else}
	      		<input type="hidden" name="field_ids[]" value="0">
				<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;">
			{/if}
			<br>
			<br>
		{/foreach}
		</blockquote>
		{/if}
		
      	<h2>Message:</h2>	
		<textarea name="content" rows="10" cols="60" style="width:98%;">{$last_content|escape}</textarea><br>
		<br>
		
		{if $captcha_enabled}
	      	<h2>{$translate->_('portal.public.captcha_instructions')}</h2>	
			{$translate->_('portal.sc.public.contact.text')} <input name="captcha" class="question" value="" size="10" autocomplete="off"><br>
			<div style="padding-top:10px;padding-left:10px;"><img src="{devblocks_url}c=captcha{/devblocks_url}"></div>
		{/if}
		
		<br>
		<b>{$translate->_('portal.public.logged_ip')}</b> {$fingerprint.ip}<br>
		<br>
		
		<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" align="top" border="0"> {$translate->_('portal.public.send_message')}</button>
		<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" align="top" border="0"> {$translate->_('common.discard')|capitalize}</button>
		
      </td>
    </tr>
    
  </tbody>
</table>
</form>
<br>
