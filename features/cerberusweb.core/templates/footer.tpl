<br>

<table align="center" border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
      <td valign="top">
      	{$fair_pay=CerberusLicense::getInstance()}
      	<b>Cerb6</b>&trade;, Cerberus Helpdesk&trade;, Devblocks&trade;, Apptendant&trade; &copy; 2002-2012, WebGroup Media LLC - Version {$smarty.const.APP_VERSION} (Build {$smarty.const.APP_BUILD}) 
      	<br>
      	{if (1 || $debug) && !empty($render_time)}
		<span style="color:rgb(180,180,180);font-size:90%;">
		page generated in: {math equation="x*1000" x=$render_time format="%d"} ms; {if !empty($render_peak_memory)} peak memory used: {$render_peak_memory|devblocks_prettybytes:2}{/if} 
		 -  
      	{if !$fair_pay->key}
      	No License (Evaluation Edition)
      	{else}
      	Licensed{if !is_null($fair_pay->company)} to {$fair_pay->company}{/if}
      	{/if}
      	<br>
      	{/if}
		</span>
      </td>
      <td  valign="top" align="right">
      	<a href="http://www.cerberusweb.com/" target="_blank"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/powered_by_cerb6.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" border="0"></span></a>
      </td>
    </tr>
</table>
<br>

</body>
</html>
