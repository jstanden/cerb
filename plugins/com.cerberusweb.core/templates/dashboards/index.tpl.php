<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap"><h1>Dashboards</h1></td>
      <td nowrap="nowrap" width="0%"><img src="images/spacer.gif" width="10">
      	<a href="#" class="smallLink">add view</a> | 
      	<a href="#" class="smallLink">customize</a> | 
      	<a href="#" class="smallLink">remove</a> | 
      	<a href="#" class="smallLink">create new ticket</a> | 
      	<a href="#" class="smallLink">refresh</a></td>
      <td align="right" nowrap="nowrap" width="100%">
      	<b>Dashboard:</b> 
      	<select name="">
      	</select>
      	<input type="submit" value="switch"> <a href="#" class="smallLink">add dashboard</a>
      </td>
    </tr>
  </tbody>
</table>
<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" valign="top">
      
      {include file="file:$path/dashboards/team_loads.tpl.php"}
      <br>
      {include file="file:$path/dashboards/mailbox_loads.tpl.php"}
      	
      </td>
      <td nowrap="nowrap" width="0%"><img src="images/spacer.gif" width="5" height="1"></td>
      <td nowrap="nowrap" width="100%" valign="top">
      
      {include file="file:$path/dashboards/ticket_view.tpl.php" id="1"}
      <br>
      {include file="file:$path/dashboards/ticket_view.tpl.php" id="2"}
      <br>
      
      {include file="file:$path/dashboards/whos_online.tpl.php"}
      	
      </td>
    </tr>
  </tbody>
</table>

