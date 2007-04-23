<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveRequester">
<input type="hidden" name="id" value="{$ticket->id}">

<table class="tableBlue" border="0" cellpadding="2" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td colspan="2" class="tableThBlue" nowrap="nowrap"> <img src="{devblocks_url}images/mail2.gif{/devblocks_url}"> Requesters </td>
    </tr>
    {assign var=requesters value=$ticket->getRequesters()}
    {foreach from=$requesters item=requester name=requesters}
    <tr>
      <td><a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showContactPanel&address={$requester->email}',this);" style="font-size:85%;" title="{$requester->email|escape:"htmlall"}">{if !empty($requester->personal)}{$requester->personal|escape:"htmlall"}{else}{$requester->email}{/if}</a></td>
      <td><a href="javascript:;" onclick="genericAjaxGet('','c=display&a=removeRequester&id={$ticket->id}&address_id={$requester->id}',ajax.refreshRequesters('{$ticket->id}'))"><b>X</b></a></td>
    </tr>
    {/foreach}
    <tr>
    	<td colspan="2" nowrap="nowrap">
    		<b>Add Requesters:</b> (by e-mail)<br>
    		<div class="automod">
	   		<div class="autocomplete">
 			<input name="add_requester" type="text" size="22" class="autoinput" id="addRequesterEntry">
 			<input type="button" value="..." onclick="">
 			<div id="addRequesterContainer" class="autocontainer"></div>
 			</div>
 			</div>
    		<div align="right"><input type="button" onclick="ajax.saveRequester('{$ticket->id}');" value="Add" title="Add requester to ticket"></div>
    	</td>
    </tr>
  </tbody>
</table>