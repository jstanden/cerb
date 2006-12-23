<input type="hidden" name="c" value="core.module.display">
<input type="hidden" name="a" value="saveRequester">
<input type="hidden" name="id" value="{$ticket->id}">

<table class="tableBlue" border="0" cellpadding="2" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td class="tableThBlue" nowrap="nowrap"> <img src="images/mail2.gif"> Requesters </td>
    </tr>
    {assign var=requesters value=$ticket->getRequesters()}
    {foreach from=$requesters item=requester name=requesters}
    <tr>
      <td><a href="javascript:;" onclick="ajax.showContactPanel('{$requester->email}',this);" style="font-size:85%;" title="{$requester->personal|escape:"htmlall"}">{$requester->email}</a></td>
    </tr>
    {/foreach}
    <tr>
    	<td>
    		<div class="automod">
	   		<div class="autocomplete">
 			<input name="add_requester" type="text" style="width:98%;" class="autoinput" id="addRequesterEntry">
 			<div id="addRequesterContainer" class="autocontainer"></div>
 			</div>
 			</div>
    		<div align="right"><input type="button" onclick="ajax.saveRequester('{$ticket->id}');" value="Add" title="Add requester to ticket"></div>
    	</td>
    </tr>
  </tbody>
</table>