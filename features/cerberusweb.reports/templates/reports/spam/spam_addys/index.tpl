<fieldset class="peek">

<legend>{'reports.ui.spam.senders'|devblocks_translate}</legend>

<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td width="50%" align="center" valign="top">
			<h3>{'reports.ui.spam.senders.top_spam'|devblocks_translate}</h3>
			<table cellpadding="5" cellspacing="0">
				<tr>
					<td><b>{'common.email'|devblocks_translate|capitalize}</b></td>
					<td align="center"><b>{'reports.ui.spam.num_spam'|devblocks_translate}</b></td>
					<td align="center"><b>{'reports.ui.spam.num_nonspam'|devblocks_translate}</b></td>
					<td align="center"><b>%</b></td>
				</tr>
				{foreach from=$top_spam_addys key=email item=counts}
				<tr>
					<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&email={$email|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$email}">{$email|truncate:45}</td>
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$counts.0}</td>
					<td align="center" style="color:rgb(0,200,0);font-weight:bold;">{$counts.1}</td>
					<td align="center">{if $counts.0 + $counts.1 > 0}{math equation="(s/(s+n))*100" s=$counts.0 n=$counts.1 format="%0.1f"}%{/if}</td>
				</tr>
				{/foreach}
			</table>
		</td>
		<td width="50%" align="center" style="padding-left:30px;" valign="top">
			<h3>{'reports.ui.spam.senders.top_nonspam'|devblocks_translate}</h3>
			<table cellpadding="5" cellspacing="0">
				<tr>
					<td><b>{'common.email'|devblocks_translate|capitalize}</b></td>
					<td align="center"><b>{'reports.ui.spam.num_nonspam'|devblocks_translate}</b></td>
					<td align="center"><b>{'reports.ui.spam.num_spam'|devblocks_translate}</b></td>
					<td align="center"><b>%</b></td>
				</tr>
				{foreach from=$top_nonspam_addys key=email item=counts}
				<tr>
					<td><a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_ADDRESS}&email={$email|escape:'url'}&view_id={$view->id}',null,false,'500');" title="{$email}">{$email|truncate:45}</td>
					<td align="center" style="color:rgb(0,200,0);font-weight:bold;">{$counts.1}</td>
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$counts.0}</td>
					<td align="center">{if $counts.0 + $counts.1 > 0}{math equation="(n/(n+s))*100" s=$counts.0 n=$counts.1 format="%0.1f"}%{/if}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>

</fieldset>