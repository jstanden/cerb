<br>

<table align="center" border="0" cellpadding="2" cellspacing="0" width="100%">
    <tr>
      <td nowrap="nowrap" valign="top">
      	{$fair_pay=CerberusLicense::getInstance()}
      	<b>Cerberus Helpdesk</b>&trade; &copy; 2002-2011, WebGroup Media&trade; LLC - Version {$smarty.const.APP_VERSION} (Build {$smarty.const.APP_BUILD}) 
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
      	<a href="http://www.cerberusweb.com/" target="_blank"><span class="cerb-sprite sprite-logo_small"></span></a>
      </td>
    </tr>
</table>
<br>

</body>
</html>
