<table cellpadding="5" cellspacing="0" border="0">
<tr>
<td style="padding-right:20px;" valign="top">
	<h2>{'spamanalysis.ui.analysis.original'|devblocks_translate|capitalize}</h2>
	{if !empty($words)}
	<table cellspacing="0" cellpadding="3" border="0">
		<tr>
			<td><b>{'spamanalysis.ui.word'|devblocks_translate|capitalize}</b></td>
			<td><b>{'spamanalysis.ui.probability'|devblocks_translate|capitalize}</b></td>
			<td><b>{'spamanalysis.ui.num_spam'|devblocks_translate|capitalize}</b></td>
			<td><b>{'spamanalysis.ui.num_nonspam'|devblocks_translate|capitalize}</b></td>
		</tr>
	{foreach from=$words item=word}
		<tr>
		<td style="margin-bottom:5px;padding-right:20px;">
			<span style="{if $word->probability >= 0.80}background-color: rgb(255, 235, 235);color:rgb(175,0,0);font-weight:bold;{elseif $word->probability <= 0.20}background-color:rgb(235, 255, 235);color:rgb(0,175,0);font-weight:bold;{else}{/if}">{$word->word}</span>
		</td>
		<td>
			{math equation="(x*100)" x=$word->probability format="%0.2f"}%
		</td>
		<td>
			{$word->spam}
		</td>
		<td>
			{$word->nonspam}
		</td>
		</tr>
	{/foreach}
	</table>
	{else}
		{'spamanalysis.ui.no_data'|devblocks_translate}
	{/if}
</td>

{if $analysis.probability}
<td style="padding-left:20px;border-left:1px solid rgb(230,230,230);" valign="top">
	<h2>{'spamanalysis.ui.analysis.live'|devblocks_translate|capitalize} ({math equation="x*100" x=$analysis.probability format="%0.2f"}%)</h2>
	{if !empty($analysis.words)}
	<table cellspacing="0" cellpadding="3" border="0">
		<tr>
			<td><b>{'spamanalysis.ui.word'|devblocks_translate|capitalize}</b></td>
			<td><b>{'spamanalysis.ui.probability'|devblocks_translate|capitalize}</b></td>
			<td><b>{'spamanalysis.ui.num_spam'|devblocks_translate|capitalize}</b></td>
			<td><b>{'spamanalysis.ui.num_nonspam'|devblocks_translate|capitalize}</b></td>
		</tr>
	{foreach from=$analysis.words item=word}
		<tr>
		<td style="margin-bottom:5px;padding-right:20px;">
			<span style="{if $word->probability >= 0.80}background-color: rgb(255, 235, 235);color:rgb(175,0,0);font-weight:bold;{elseif $word->probability <= 0.20}background-color:rgb(235, 255, 235);color:rgb(0,175,0);font-weight:bold;{else}{/if}">{$word->word}</span>
		</td>
		<td>
			{math equation="(x*100)" x=$word->probability format="%0.2f"}%
		</td>
		<td>
			{$word->spam}
		</td>
		<td>
			{$word->nonspam}
		</td>
		</tr>
	{/foreach}
	</table>
	{else}
		{'spamanalysis.ui.not_enough_data'|devblocks_translate}
	{/if}
</td>
{/if}
</tr>
</table>