{if !empty($last_error)}
	<div class="error" style="width:550px;">
		{$last_error}
	</div>
{/if}

<form id="openJournalForm" action="{devblocks_url}c=server{/devblocks_url}" method="post" enctype="multipart/form-data">
	<input type="hidden" name="a" value="doJournalEntrySave" />
	<input type="hidden" name="" />
	<table border="0" cellpadding="0" cellspacing="0" width="99%">
		<tbody>
			<tr>
				<td colspan="2">
					<fieldset>
						<legend>{$translate->_('common.new_journal_entry')}</legend>
						
						<b>{$translate->_('dao.cerb_plugin.author')}:</b> {$address->getName()} &lt;{$address->email}&gt;<br />
						<div>
							<textarea name="journal" rows="5" cols="60" style="width:98%;">{$last_content}</textarea>
						</div>
						<br />
						<b>{$translate->_('portal.sc.public.server.server')}:</b><br />
						<select name="context_id">
							<option value=""></option>
						{foreach from=$servers item=server name=srv}
							<option value="{$server->id}"{if !empty($last_server) && $last_server == $server_id} selected="selected"{/if}>{$server->name}</option>
						{/foreach}		
						</select>
						<br /><br />
					</fieldset>
						
					<fieldset>
						<legend>{$translate->_('common.attachments')}</legend>
						<input type="file" name="attachments[]" class="multi" />
						<br />
					</fieldset>
						
					<fieldset>
						<legend>{$translate->_('portal.public.captcha_instructions')}</legend>
						{$translate->_('portal.sc.public.contact.text')} <input type="text" id="captcha" class="question" name="captcha" value="" size="10" autocomplete="off" /><br />
						<div style="padding: 10px 0 0 10px;">
							<img src="{devblocks_url}c=captcha{/devblocks_url}?color=0,0,0&bgcolor=235,235,235" alt="captcha" />
						</div>
						<br />
					</fieldset>
					
					<br />
					<b>{$translate->_('portal.public.logged_ip')}</b> {$fingerprint.ip}
					<br />
					
					<div class="buttons">
						<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/check.gif{/devblocks_url}" align="top" border="0" /> {$translate->_('portal.public.save')|capitalize}</button>
						<button type="button" onclick="document.location='{devblocks_url}c=server{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.support_center&f=images/delete.gif{/devblocks_url}" /> {$translate->_('common.discard')|capitalize}</button>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</form>
					
{literal}
<script type="text/javascript">
	$(document).ready(function() {
		$frm = $("#openJournalForm");
		$frm.validate({
			submitHandler: function(form) {
				$(form).find('div.buttons').hide();
				form.submit();
			},
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