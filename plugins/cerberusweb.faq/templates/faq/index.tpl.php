{include file="file:$path/faq/menu.tpl.php"}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="0%" valign="top">

      {*include file="file:$path/tickets/teamwork_menu.tpl.php"*}
      [[ faq options ]]
      
      </td>
      <td nowrap="nowrap" width="0%"><img src="{devblocks_url}images/spacer.gif{/devblocks_url}" width="5" height="1"></td>
      <td nowrap="nowrap" width="100%" valign="top">

		[[ faq list (searchDAO) ]] 
      
      </td>
    </tr>
  </tbody>
</table>

