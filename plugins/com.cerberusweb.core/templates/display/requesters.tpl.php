<table class="tableBlue" border="0" cellpadding="2" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td class="tableThBlue" nowrap="nowrap"> <img src="images/mail2.gif"> Requesters</td>
    </tr>
    {assign var=requesters value=$ticket->getRequesters()}
    {foreach from=$requesters item=requester name=requesters}
    <tr>
      <td><a href="javascript:;" style="font-size:85%;" title="{$requester->personal|escape:"htmlall"}">{$requester->email}</a></td>
    </tr>
    {/foreach}
    <tr>
    	<td><input type="text"><input type="button" value="+" title="Add requester to ticket"></td>
    </tr>
  </tbody>
</table>
