{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form id="openTicketForm" action="{devblocks_url}c=contact{/devblocks_url}" method="post" enctype="multipart/form-data">
<input type="hidden" name="a" value="doContactSend">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">
<table border="0" cellpadding="0" cellspacing="0" width="99%">
	<tbody>
	<tr>
	<td colspan="2">
		<fieldset>
			<legend>{'portal.common.open_ticket'|devblocks_translate}:</legend>
			
			<b>{'portal.public.what_email_reply'|devblocks_translate}</b><br>
			<input type="hidden" name="nature" value="{$sNature}">
			
			{if empty($last_from) && !empty($active_contact)}
				{$primary_email = $active_contact->getEmail()}
				{$last_from = $primary_email->email}
			{/if}
			
			<input type="text" name="from" value="{if !empty($last_from)}{$last_from}{/if}" autocomplete="off" style="width:100%;" class="required email"><br>
			<br>
			
			{if $allow_cc}
			<b>{'message.header.cc'|devblocks_translate|capitalize}:</b> ({'common.help.comma_separated'|devblocks_translate|lower})<br>
			<input type="text" name="cc" value="{if !empty($last_cc)}{$last_cc}{/if}" autocomplete="off" style="width:100%;"><br>
			<br>
			{/if}
			
			<b>{'ticket.subject'|devblocks_translate|capitalize}:</b><br>
			{if $allow_subjects}
			<input type="text" name="subject" value="{if !empty($last_subject)}{$last_subject}{/if}" placeholder="{$situation}" autocomplete="off" style="width:100%;" class="required"><br>
			{else}
			{$situation}<br>
			{/if}
			<br>
			
			<b>{'portal.public.open_ticket.message'|devblocks_translate}:</b><br>
			<textarea name="content" rows="15" cols="60" style="width:100%;" class="required">{$last_content}</textarea><br>
		</fieldset>

		{if !empty($situation_params.followups)}
		<fieldset>
			<legend>{'portal.public.open_ticket.additional_info'|devblocks_translate}</legend>
			
			{foreach from=$situation_params.followups key=question item=field_id name=situations}
				{$idx = $smarty.foreach.situations.iteration-1}
				{$required = '*'==substr($question,0,1)}
				{$field = $ticket_fields.$field_id}

				<h2>{$question}</h2>
				<input type="hidden" name="followup_q[]" value="{$question}">
				{if !empty($field_id)}
					<input type="hidden" name="field_ids[]" value="{$field_id}">
					
					{if $field->type==Model_CustomField::TYPE_SINGLE_LINE}
						<input type="text" name="followup_a_{$idx}" value="{$last_followup_a[$idx]|default:''}" autocomplete="off" style="width:100%;" class="{if $required}required{/if}">
					{elseif $field->type==Model_CustomField::TYPE_URL}
						<input type="text" name="followup_a_{$idx}" value="{$last_followup_a[$idx]|default:''}" autocomplete="off" style="width:100%;" class="url {if $required}required{/if}">
					{elseif $field->type==Model_CustomField::TYPE_NUMBER}
						<input type="text" name="followup_a_{$idx}" size="12" maxlength="20" value="{$last_followup_a[$idx]|default:''}" autocomplete="off" class="number {if $required}required{/if}">
					{elseif $field->type==Model_CustomField::TYPE_MULTI_LINE}
						<textarea name="followup_a_{$idx}" rows="5" cols="60" style="width:100%;" class="{if $required}required{/if}">{$last_followup_a.$idx}</textarea>
					{elseif $field->type==Model_CustomField::TYPE_DROPDOWN}
						<select name="followup_a_{$idx}" class="{if $required}required{/if}">
							<option value=""></option>
							{foreach from=$field->params.options item=opt}
							<option value="{$opt}" {if $last_followup_a.$idx==$opt}selected="selected"{/if}>{$opt}
							{/foreach}
						</select>
					{elseif $field->type==Model_CustomField::TYPE_WORKER}
						<select name="followup_a_{$idx}" class="{if $required}required{/if}">
							<option value=""></option>
							{foreach from=$workers item=worker key=worker_id}
							<option value="{$worker_id}" {if $last_followup_a.$idx==$worker_id}selected="selected"{/if}>{$worker->getName()}</option>
							{/foreach}
						</select>
					{elseif $field->type==Model_CustomField::TYPE_DATE}
						<input type="text" name="followup_a_{$idx}" value="{$last_followup_a[$idx]|default:''}" autocomplete="off" class="date {if $required}required{/if}">
					{elseif $field->type==Model_CustomField::TYPE_MULTI_CHECKBOX}
						{foreach from=$field->params.options item=opt}
						<label><input type="checkbox" name="followup_a_{$idx}[]" value="{$opt}"> {$opt}</label><br>
						{/foreach}
					{elseif $field->type==Model_CustomField::TYPE_CHECKBOX}
						<label><input name="followup_a_{$idx}" type="checkbox" value="Yes" {if $last_followup_a.$idx}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
					{elseif $field->type==Model_CustomField::TYPE_FILE}
						<input type="file" name="followup_a_{$idx}">
					{elseif $field->type==Model_CustomField::TYPE_FILES}
						<input type="file" name="followup_a_{$idx}[]" multiple="multiple">
					{elseif $field->type==Model_CustomField::TYPE_LINK}
						{* N/A *}
					{elseif $field->type==Model_CustomField::TYPE_DECIMAL}
						<input type="text" name="followup_a_{$idx}" size="24" maxlength="64" value="{$last_followup_a[$idx]|default:''}" class="decimal">
					{elseif $field->type==Model_CustomField::TYPE_CURRENCY}
						{$currency = $currencies[$field->params.currency_id]}
						{if $currency}
							{$currency->symbol}
							<input type="text" name="followup_a_{$idx}" size="24" maxlength="64" value="{$last_followup_a[$idx]|default:''}" class="currency">
							{$currency->code}
						{else}
							<input type="text" name="followup_a_{$idx}" size="24" maxlength="64" value="{$last_followup_a[$idx]|default:''}" class="currency">
						{/if}
					{/if}
					
				{else}
					<input type="hidden" name="field_ids[]" value="0">
					<input type="text" name="followup_a_{$idx}" value="{$last_followup_a[$idx]|default:''}" autocomplete="off" style="width:100%;" class="{if $required}required{/if}">
				{/if}
				<br>
				<br>
			{/foreach}
		</fieldset>
		{/if}

		{if 0 == $attachments_mode || (1 == $attachments_mode && !empty($active_contact))}
		<fieldset>
			<legend>Attachments:</legend>
			<input type="file" name="attachments[]" multiple="multiple"><br>
		</fieldset>
		{/if}
		
		{if 1 == $captcha_enabled || (2 == $captcha_enabled && empty($active_contact))}
		<fieldset>
			<legend>{'portal.public.captcha_instructions'|devblocks_translate}</legend>
			{'portal.sc.public.contact.text'|devblocks_translate} <input type="text" id="captcha" name="captcha" class="question" value="" size="10" autocomplete="off"><br>
			<div style="padding-top:10px;padding-left:10px;"><img src="{devblocks_url}c=captcha{/devblocks_url}?color=0,0,0&bgcolor=235,235,235"></div>
		</fieldset>
		{/if}
		
		<br>
		<b>{'portal.public.logged_ip'|devblocks_translate}</b> {$client_ip}<br>
		<br>
		
		<div class="buttons">
			<button type="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'portal.public.send_message'|devblocks_translate}</button>
			<button type="button" onclick="document.location='{devblocks_url}{/devblocks_url}';"><span class="glyphicons glyphicons-circle-remove"></span> {'common.discard'|devblocks_translate|capitalize}</button>
		</div>
	</td>
	</tr>
	
	</tbody>
</table>
</form>