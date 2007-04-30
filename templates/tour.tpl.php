{if !empty($tour)}
<table width="100%" style="background-color:rgb(240,240,255);border:1px solid rgb(0,0,255);">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"></td>
		<td align="center" width="100%"><h1>Tour: {$tour.title}</h1></td>
		<td align="right" width="0%" nowrap="nowrap">
			<form action="{devblocks_url}{/devblocks_url}" method="post" name="tourForm">
				<input type="hidden" name="c" value="tickets">
				<input type="hidden" name="a" value="doStopTour">
				<a href="javascript:;" onclick="document.tourForm.submit();">close tour</a>
				&nbsp;
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<p>This is where all the text goes that explains what this section of the tour is 
			covering.</p>
			
			{if !empty($tour.callouts)}
			<p>
			<b>Spotlights:</b><br>
			{foreach from=$tour.callouts item=callout key=callout_div name=callouts}
				<a href="javascript:;" onclick="genericAjaxPanel('c=tickets&a=showCallout&id={$callout_div}','{$callout_div}',false);">{$callout}</a>
				{if !$smarty.foreach.callouts.last} | {/if}
			{/foreach}
			</p>
			{/if}
		</td>
	</tr>
</table>
{/if}