<br>

<table align="center" border="0" cellpadding="2" cellspacing="0" width="100%">
	<tr>
		<td valign="top">
			{$fair_pay=CerberusLicense::getInstance()}
			<b>Cerb</b>&trade;, Devblocks&trade; &copy; 2002-{time()|devblocks_date:'Y'}, Webgroup Media LLC - Version {$smarty.const.APP_VERSION} (Build {$smarty.const.APP_BUILD}) 
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
		<td valign="top" align="right">
			<a href="https://cerb.ai/" target="_blank" rel="noopener"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/powered_by_cerb.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" border="0"></a>
		</td>
	</tr>
</table>
<br>

{if $active_worker && $global_interactions_show}{include file="devblocks:cerberusweb.core::console/bot_interactions_button.tpl"}{/if}

<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/ace/ace.js{/devblocks_url}"></script>
<script type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/ace/ext-language_tools.js{/devblocks_url}"></script>
</body>
</html>
