<table cellspacing="2" cellpadding="2" border="0">
	<tr>
		<td colspan="2"><b>{$translate->_('portal.sc.cfg.view_ticket.ticket_view_url')}</b></td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="text" name="ticket_view_url" value="{$ticket_view_url}" size="65" />
		</td>
	</tr>
	<tr>
		<td colspan="2"><b>{$translate->_('portal.sc.cfg.view_ticket.view_settings')}</b></td>
	</tr>
	{$ticket_fields = [ticket_change_status, ticket_answer]}
	{$ticket_labels = [$translate->_('portal.sc.cfg.view_ticket.ticket_change_status'), $translate->_('portal.sc.cfg.view_ticket.ticket_answer')]}
	{foreach from=$ticket_fields item=field name=fields}
	<tr>
		<td style="width: 130px;">
			<input type="hidden" name="fields[]" value="{$field}" />
			<select name="fields_editable[]">
				<option value="0">{$translate->_('portal.sc.cfg.view_ticket.editable_no')}</option>
				<option value="1"{if $show_fields.{$field} == 1} selected="selected"{/if}>
					{if $field == 'ticket_change_status'}
						{$text = 'portal.sc.cfg.view_ticket.editable_closeonly'}
					{else}
						{$text = 'portal.sc.cfg.view_ticket.editable_text'}
					{/if}
					{$translate->_($text)}
				</option>
				<option value="2"{if $show_fields.{$field} == 2} selected="selected"{/if}>{$translate->_('portal.sc.cfg.view_ticket.editable_like_history')}</option>
			</select>
		</td>
		<td>
			{$ticket_labels.{$smarty.foreach.fields.index}|capitalize}
		</td>
	</tr>
	{/foreach}
</table>
<br />