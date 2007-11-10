<img alt="powered by cerberus helpdesk" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/cerberus_logo_small.gif{/devblocks_url}" border="0">

<h2 style="color: rgb(102,102,102);">My Tickets</h2>

{if $mytickets}
<table border="0" cellpadding="0" cellspacing="5">
{foreach from=$mytickets item=ticket}
	<tr>
		<td align="right"><a href="{devblocks_url}c=mobile&a=display&id={$ticket.t_mask}{/devblocks_url}">{$ticket.t_mask}</a> : </td>
		<td>{$ticket.t_subject}</td>
	</tr>
{/foreach}
</table>
{else}
You have no active tickets.<br>
{/if}