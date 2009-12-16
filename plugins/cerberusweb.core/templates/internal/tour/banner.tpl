{if !empty($tour)}
<div id="tourDiv" style="display:block;">
<table width="100%" style="background-color:rgb(240,240,255);border:1px solid rgb(0,0,255);">
	<tr>
		<td align="left" width="10%" nowrap="nowrap"></td>
		<td align="center" width="80%"><h1>Assist Mode: {$tour.title}</h1></td>
		<td align="right" width="10%" nowrap="nowrap">
			<form action="{devblocks_url}{/devblocks_url}" method="post" name="tourForm">
				<input type="hidden" name="c" value="internal">
				<input type="hidden" name="a" value="doStopTour">
				<a href="javascript:;" onclick="toggleDiv('tourDiv');document.tourForm.submit();">close assistant</a>
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
				<img src="{devblocks_url}c=resource&a=cerberusweb.core&f=images/help.gif{/devblocks_url}" align="absmiddle"> <a href="#{$callout->id}" onclick="genericAjaxPanel('c=internal&a=showCallout&id={$callout->id}','{$callout->id}',false);">{$callout->title}</a>
			{/foreach}
			</p>
			{/if}
		</td>
	</tr>
</table>
<br>
</div>
{/if}