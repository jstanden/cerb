{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<div id="contact">
<form id="openTicketForm" action="{devblocks_url}c=contact{/devblocks_url}" method="post" enctype="multipart/form-data">
<input type="hidden" name="a" value="doContactSend">
<table border="0" cellpadding="0" cellspacing="0" width="99%">
  <tbody>
    <tr>
      <td colspan="2">
		<div class="header"><h1>{$translate->_('portal.common.open_ticket')}</h1></div>
      
      	<h2>{$translate->_('portal.public.what_email_reply')}</h2>
      	<input type="hidden" name="nature" value="{$sNature}">	
		<input name="from" value="{if !empty($last_from)}{$last_from|escape}{else}{$active_user->email|escape}{/if}" autocomplete="off" style="width:98%;" class="required email"><br>
		<br>

      	<h2>{$translate->_('ticket.subject')|capitalize}:</h2>
      	{if $allow_subjects}
		<input type="text" name="subject" value="{if !empty($last_subject)}{$last_subject|escape}{/if}" autocomplete="off" style="width:98%;" class="required"><br>
		{else}
		{$situation|escape}<br>
		{/if}
		<br>
		
      	{if !empty($situation_params.followups)}
		<div class="header"><h1>{$translate->_('portal.public.open_ticket.additional_info')}</h1></div>
      	
      	<blockquote style="margin-left:10px;">
		{foreach from=$situation_params.followups key=question item=field_id name=situations}
			{math assign=idx equation="x-1" x=$smarty.foreach.situations.iteration}

			{if '*'==substr($question,0,1)}
				{assign var=required value=true}
			{else}
				{assign var=required value=false}
			{/if}
	      	
	      	<h2>{$question}</h2>
	      	<input type="hidden" name="followup_q[]" value="{$question|escape}">
	      	{if !empty($field_id)}
	      		{assign var=field value=$ticket_fields.$field_id}
				<input type="hidden" name="field_ids[]" value="{$field_id|escape}">
	      		
	      		{if $field->type=='S'}
	      			<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;" class="{if $required}required{/if}">
	      		{elseif $field->type=='U'}
	      			<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;" class="url {if $required}required{/if}">
	      		{elseif $field->type=='N'}
	      			<input name="followup_a_{$idx}" size="12" maxlength="20" value="{$last_followup_a.$idx|escape}" autocomplete="off" class="number {if $required}required{/if}">
	      		{elseif $field->type=='T'}
	      			<textarea name="followup_a_{$idx}" rows="5" cols="60" style="width:98%;" class="{if $required}required{/if}">{$last_followup_a.$idx|escape}</textarea>
	      		{elseif $field->type=='D'}
	      			<select name="followup_a_{$idx}" class="{if $required}required{/if}">
	      				<option value=""></option>
	      				{foreach from=$field->options item=opt}
	      				<option value="{$opt|escape}" {if $last_followup_a.$idx==$opt}selected="selected"{/if}>{$opt|escape}
	      				{/foreach}
	      			</select>
				{elseif $field->type=='M'}
					<select name="followup_a_{$idx}[]" size="5" multiple="multiple">
						{foreach from=$field->options item=opt}
						<option value="{$opt|escape}">{$opt|escape}</option>
						{/foreach}
					</select><br>
					<i><small>{$translate->_('common.tips.multi_select')}</small></i>
	      		{elseif $field->type=='W'}
					{if empty($workers)}
						{$workers = DAO_Worker::getAllActive()}
					{/if}
	      			<select name="followup_a_{$idx}" class="{if $required}required{/if}">
	      				<option value=""></option>
	      				{foreach from=$workers item=worker key=worker_id}
	      				<option value="{$worker_id}" {if $last_followup_a.$idx==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
	      				{/foreach}
	      			</select>
	      		{elseif $field->type=='E'}
	      			<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" class="date {if $required}required{/if}">
				{elseif $field->type=='X'}
					{foreach from=$field->options item=opt}
					<label><input type="checkbox" name="followup_a_{$idx}[]" value="{$opt|escape}"> {$opt}</label><br>
					{/foreach}
	      		{elseif $field->type=='C'}
	      			<label><input name="followup_a_{$idx}" type="checkbox" value="Yes" {if $last_followup_a.$idx}checked="checked"{/if}> {$translate->_('common.yes')|capitalize}</label>
	      		{/if}
	      		
	      	{else}
	      		<input type="hidden" name="field_ids[]" value="0">
				<input name="followup_a_{$idx}" value="{$last_followup_a.$idx|escape}" autocomplete="off" style="width:98%;" class="{if $required}required{/if}">
			{/if}
			<br>
			<br>
		{/foreach}
		</blockquote>
		{/if}
		
		<div class="header"><h1>{$translate->_('portal.public.open_ticket.message')}</h1></div>
		<textarea name="content" rows="15" cols="60" style="width:98%;" class="required">{$last_content|escape}</textarea><br>
		<br>
		
		{if 0==$attachments_mode || (1==$attachments_mode && !empty($active_user))}
		<div class="header"><h1>Attachments</h1></div>
		<input type="file" name="attachments[]" class="multi"><br>
		<br>
		{/if}
		
		{if $captcha_enabled}
			<div class="header"><h1>{$translate->_('portal.public.captcha_instructions')}</h1></div>
			{$translate->_('portal.sc.public.contact.text')} <input id="captcha" name="captcha" class="question" value="" size="10" autocomplete="off"><br>
			<div style="padding-top:10px;padding-left:10px;"><img src="{devblocks_url}c=captcha{/devblocks_url}?color=0,0,0&bgcolor=235,235,235"></div>
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
</div>

{literal}
<script language="JavaScript1.2" type="text/javascript">
  $(document).ready(function(){
    $("#openTicketForm").validate({
		rules: {
			captcha: {
				required: true,
				minlength: 4,
				remote: "{/literal}{devblocks_url}c=captcha.check{/devblocks_url}{literal}"
			}
		},
		messages: {
			captcha: {
				required: "Enter the text from the image",
				minlength: jQuery.format("Enter at least {0} characters"),
				remote: jQuery.format("That is not correct. Try again!")
			}
		}		
	});
  });
</script>
{/literal}