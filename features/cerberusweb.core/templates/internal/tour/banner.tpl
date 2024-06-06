{if !empty($tour)}
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
				<a data-cerb-tour-hide>hide this</a>
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
					<span class="glyphicons glyphicons-pushpin"></span> <a href="#" id="callout{$callout_id}">{$callout->title}</a>
					&nbsp; 
				{/foreach}
				</div>
			{/if}
		</td>
	</tr>
</table>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $tour = $('#tourDiv');

	$tour.find('[data-cerb-tour-hide]').on('click', function(e) {
		e.stopPropagation();
		$('#tourDiv').fadeOut();
		genericAjaxPost($('#formTour'));
	});

	let $tooltip = $('<span/>')
		.tooltip({
		})
		.appendTo($tour)
		.hide()
	;
{foreach from=$tour.callouts item=callout key=callout_id name=callouts}
$('#tourDiv A#callout{$callout_id}')
	.on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		$tooltip.tooltip('close');

		let $sel = $('{$callout->selector nofilter}');
		
		$tooltip.attr('title', "{$callout->body}");
		$tooltip.tooltip('option', 'position', {
			my: "{$callout->tipCorner}",
			at: "{$callout->targetCorner}",
			of: $sel,
			using: function(position, feedback) {
				$(this).css(position);
				$("<div>")
					.addClass("arrow")
					.addClass(feedback.vertical)
					.addClass(feedback.horizontal)
					.appendTo(this)
				;
			},
			collision: "flipfit"
		});
		
		$tooltip.tooltip('open');
	});
{/foreach}
});
</script>
{/if}