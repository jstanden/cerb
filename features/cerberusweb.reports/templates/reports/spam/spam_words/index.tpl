<ul class="submenu">
</ul>
<div style="clear:both;"></div>

<h2>{$translate->_('reports.ui.spam.words')}</h2>

<h3>{$translate->_('reports.ui.spam.words.spam_training')}</h3>
{$translate->_('reports.ui.spam.words.num_nonspam_trained')} <span style="color:rgb(0,200,0);font-weight:bold;">{$num_nonspam}</span><br> 
{$translate->_('reports.ui.spam.words.num_spam_trained')} <span style="color:rgb(200,0,0);font-weight:bold;">{$num_spam}</span><br>
<br>

<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td width="50%" align="center" valign="top">
			<h3>{$translate->_('reports.ui.spam.words.top_spam')}</h3>
			<table cellpadding="5" cellspacing="0">
				<tr>
					<td><b>{$translate->_('reports.ui.spam.words.word')}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_spam')}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_nonspam')}</b></td>
					<td align="center"><b>%</b></td>
				</tr>
				{foreach from=$top_spam_words key=word item=counts}
				<tr>
					<td>{$word|escape}</td>
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$counts.0}</td>
					<td align="center" style="color:rgb(0,200,0);font-weight:bold;">{$counts.1}</td>
					<td align="center">{if $counts.0 + $counts.1 > 0}{math equation="(s/(s+n))*100" s=$counts.0 n=$counts.1 format="%0.1f"}%{/if}</td>
				</tr>
				{/foreach}
			</table>
		</td>
		<td width="50%" align="center" style="padding-left:30px;" valign="top">
			<h3>{$translate->_('reports.ui.spam.words.top_nonspam')}</h3>
			<table cellpadding="5" cellspacing="0">
				<tr>
					<td><b>{$translate->_('reports.ui.spam.words.word')}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_nonspam')}</b></td>
					<td align="center"><b>{$translate->_('reports.ui.spam.num_spam')}</b></td>
					<td align="center"><b>%</b></td>
				</tr>
				{foreach from=$top_nonspam_words key=word item=counts}
				<tr>
					<td>{$word|escape}</td>
					<td align="center" style="color:rgb(0,200,0);font-weight:bold;">{$counts.1}</td>
					<td align="center" style="color:rgb(200,0,0);font-weight:bold;">{$counts.0}</td>
					<td align="center">{if $counts.0 + $counts.1 > 0}{math equation="(n/(n+s))*100" s=$counts.0 n=$counts.1 format="%0.1f"}%{/if}</td>
				</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>

