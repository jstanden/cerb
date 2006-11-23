<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td width="0%" nowrap="nowrap"><h1>{$translate->say('dashboard.dashboards')|capitalize}</h1></td>
      <td nowrap="nowrap" width="0%"><img src="images/spacer.gif" width="10">
      	<a href="#">{$translate->say('dashboard.add_view')|lower}</a> | 
      	<a href="#">{$translate->say('common.customize')|lower}</a> | 
      	<a href="#">{$translate->say('common.remove')|lower}</a> | 
      	<a href="#">{$translate->say('dashboard.create_ticket')|lower}</a> | 
      	<a href="#">{$translate->say('common.refresh')|lower}</a></td>
      <td align="right" nowrap="nowrap" width="100%">
      	<b>{$translate->say('dashboard')|capitalize}:</b> 
      	<select name="">
      		<option value="">-- {$translate->say('dashboard.choose_dashboard')|lower} --
      	</select>
      	<input type="submit" value="{$translate->say('dashboard.switch')|lower}"> 
      	<a href="#">{$translate->say('dashboard.add_dashboard')|lower}</a>
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
      
      {foreach from=$views item=view}
      	<div id="view{$view->id}">
	      	{include file="file:$path/dashboards/ticket_view.tpl.php"}
	      </div>
	      <br>
      {/foreach}
      
      {include file="file:$path/dashboards/whos_online.tpl.php"}
      	
      </td>
    </tr>
  </tbody>
</table>

