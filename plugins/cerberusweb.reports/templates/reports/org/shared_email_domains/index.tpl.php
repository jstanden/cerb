<div id="headerSubMenu">
	<div style="padding-bottom:5px;">
	</div>
</div>

<h2>Top 100 Shared Sender Domains</h2>
<br>

<table cellpadding="5" cellspacing="0">
	<tr>
		<td align="center"><b># Orgs</b></td>
		<td><b>Domain</b></td>
	</tr>

	{foreach from=$top_domains key=domain item=count}
	<tr>
		<td align="center">{$count}</td>
		<td>{$domain}</td>
	</tr>
	{/foreach}
	
</table>

