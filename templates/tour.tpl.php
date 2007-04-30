{if !empty($tour)}
<table width="100%" style="background-color:rgb(240,240,255);border:1px solid rgb(0,0,255);">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"></td>
		<td align="center" width="100%"><h1>Tour Mode: {$tour.title}</h1></td>
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
			<p>{$tour.body}</p>
			
			{if !empty($tour.callouts)}
			<p>
			<b>Points of Interest:</b><br>
			{foreach from=$tour.callouts item=callout name=callouts}
				<img src="{devblocks_url}images/help.gif{/devblocks_url}" align="absmiddle"> <a href="#{$callout->id}" onclick="genericAjaxPanel('c=tickets&a=showCallout&id={$callout->id}','{$callout->id}',false);">{$callout->title}</a>
			{/foreach}
			</p>
			{/if}
		</td>
	</tr>
</table>
{/if}