{if !empty($tour)}
<div id="tourDiv">
<table width="100%">
	<tr>
		<td align="left" width="10%" nowrap="nowrap"></td>
		<td align="center" width="80%"><h1>Assist Mode: {$tour.title}</h1></td>
		<td align="right" width="10%" nowrap="nowrap">
			<form action="{devblocks_url}{/devblocks_url}" method="post" name="tourForm">
				<input type="hidden" name="c" value="internal">
				<input type="hidden" name="a" value="doStopTour">
				<a href="javascript:;" onclick="$('#tourDiv').fadeOut();genericAjaxGet('','c=internal&a=doStopTour');">hide this</a>
				&nbsp;
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<p>{$tour.body nofilter}</p>
			
			{if !empty($tour.callouts)}
				<b>Points of Interest:</b>
				{if !empty($tour.callouts)}
					&nbsp; <a href="javascript:;" onclick="$links=$(this).next('div').find('a');$links.removeClass('on');$links.click();">show all</a>
				{/if}
				<div style="margin:5px;">
				{foreach from=$tour.callouts item=callout key=callout_id name=callouts}
					<span class="cerb-sprite sprite-help"></span> <a href="javascript:;" id="callout{$callout_id}">{$callout->title}</a>
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
		$sel = $('{$callout->selector nofilter}');
		try {
			$sel.qtip("destroy");
		} catch(e){}
		
		if($(this).hasClass('on')) {
			$(this).removeClass('on');
		} else {
			$(this).addClass('on');
			$target = $sel
				.qtip({
					content:"{$callout->body}",
					position:{
						corner:{
							tooltip:'{$callout->tipCorner}',
							target:'{$callout->targetCorner}'
						},
						adjust:{
							x:{$callout->xOffset},
							y:{$callout->yOffset},
						}
					},
					show:{
						when:false,
						ready:true
					},
					hide:false,
					style:{
						name:'dark',
						tip:true,
						border:{
							radius:3,
							width:5
						}
					}
				})
				;
			var $this = $(this);
			$target.qtip("api").elements.tooltip.click(function(e) {
				$(this).qtip("destroy");
				$this.removeClass('on');
			})
			;
		}
	});
{/foreach}
</script>
{/if}