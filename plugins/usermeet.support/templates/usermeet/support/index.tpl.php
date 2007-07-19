{include file="$path/usermeet/support/header.tpl.php"}

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doSendMessage">
<table style="text-align: left; width: 550px;" class="search" border="0" cellpadding="5" cellspacing="5">
  <tbody>
    <tr>
      <td colspan="2">
      	<h4>Where to?</h4>
      	<button type="button" onclick="document.location='{devblocks_url}c=write{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.support&f=images/mail_new.gif{/devblocks_url}" align="top" border="0"> Send us a message</button> 
      	<!-- <button type="button" onclick="document.location='{devblocks_url}c=history{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=usermeet.support&f=images/history.gif{/devblocks_url}" align="top" border="0"> Look up a past message</button>  -->
      </td>
    </tr>
    
    <tr>
    	<td colspan="2" align="right">
    	<span style="font-size:11px;">
			Powered by <a href="http://www.cerberusweb.com/" target="_blank" style="color:rgb(80,150,0);font-weight:bold;">Cerberus Helpdesk</a>&trade;<br>
		</span>
    	</td>
    </tr>
    
  </tbody>
</table>
</form>
<br>

{include file="$path/usermeet/support/footer.tpl.php"}