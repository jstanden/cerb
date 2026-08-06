{$view_marquees = $view->marqueeFlush()}
{$marquees_id = uniqid('marquee')}
{if $view_marquees}
<div id="{$marquees_id}">
{foreach from=$view_marquees item=view_marquee}
	<div class="cerb-ui-panel cerb-ui-panel--success cerb-view-marquee" style="margin:0.5em 0;">
		<div class="cerb-ui-header">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
				<div>{$view_marquee nofilter}</div>
			</div>
			<div class="cerb-ui-header--right">
				<button type="button" class="cerb-ui-button cerb-ui-button--transparent" data-cerb-marquee-close title="Dismiss"><span class="cerb-icons cerb-icon-circle-remove"></span></button>
			</div>
		</div>
	</div>
{/foreach}
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $marquees = $('#{$marquees_id}');

	$marquees.find('[data-cerb-marquee-close]').on('click', function(e) {
		e.stopPropagation();
		$(this).closest('.cerb-view-marquee').remove();
	});
});
</script>