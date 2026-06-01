{$view_marquees = $view->marqueeFlush()}
{$marquees_id = uniqid('marquee')}
{if $view_marquees}
<div id="{$marquees_id}">
{foreach from=$view_marquees item=view_marquee}
	<div class="cerb-alert cerb-alert-rounded cerb-alert-margins-tb cerb-view-marquee">
		<div class="cerb-alert-close">
			<span class="cerb-icons cerb-icon-circle-remove"></span>
		</div>
		{$view_marquee nofilter}
	</div>
{/foreach}
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $marquees = $('#{$marquees_id}');

	$marquees.find('.cerb-alert-close').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('div.cerb-alert').remove();
	});
});
</script>