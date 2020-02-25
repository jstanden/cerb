{if !empty($tour)}
<script type="text/javascript" src="{devblocks_url}c=resource&plugin=devblocks.core&f=js/jquery/jquery.qtip.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
<link rel="stylesheet" type="text/css" href="{devblocks_url}c=resource&plugin=devblocks.core&f=css/jquery.qtip.min.css{/devblocks_url}?v={$smarty.const.APP_BUILD}" />

<div id="tourDiv" class="help-box">
<table width="100%">
	<tr>
		<td align="left" width="10%" nowrap="nowrap"></td>
		<td align="center" width="80%"><h1>Tour: {$tour.title}</h1></td>
		<td align="right" width="10%" nowrap="nowrap">
			<form action="{devblocks_url}{/devblocks_url}" method="post" name="tourForm" id="formTour">
				<input type="hidden" name="c" value="profiles">
				<input type="hidden" name="a" value="invoke">
				<input type="hidden" name="module" value="worker">
				<input type="hidden" name="action" value="stopTour">
				<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
				<a href="javascript:;" onclick="$('#tourDiv').fadeOut();genericAjaxPost($('#formTour'));">hide this</a>
				&nbsp;
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<p>{$tour.body nofilter}</p>
			
			{if !empty($tour.callouts)}
				<b>Points of Interest:</b>
				<div style="margin:5px;">
				{foreach from=$tour.callouts item=callout key=callout_id name=callouts}
					<span class="glyphicons glyphicons-pushpin"></span> <a href="javascript:;" id="callout{$callout_id}">{$callout->title}</a>
					&nbsp; 
				{/foreach}
				</div>
			{/if}
		</td>
	</tr>
</table>
</div>

<script type="text/javascript">
{foreach from=$tour.callouts item=callout key=callout_id name=callouts}
$('#tourDiv A#callout{$callout_id}')
	.click(function() {
		var $sel = $('{$callout->selector nofilter}');
		
		try {
			$sel.qtip("destroy");
		} catch(e){}
		
		$sel
			.qtip({
				content: {
					text: "{$callout->body}"
				},
				position:{
					my:'{$callout->tipCorner}',
					at:'{$callout->targetCorner}',
					adjust: {
						x:{$callout->xOffset},
						y:{$callout->yOffset}
					}
				},
				show: {
					ready: true
				},
				hide: {
					event: "unfocus"
				},
				style: {
					classes: 'qtip-dark qtip-shadow qtip-rounded',
					tip: {
						corner: true
					}
				},
				events: {
					hide: function(event, api) {
						api.destroy();
					}
				}
			})
			;
		
	});
{/foreach}
</script>
{/if}