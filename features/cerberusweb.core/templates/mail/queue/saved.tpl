{$div_id = uniqid('div')}
<div id="{$div_id}" style="text-align:right;">
	<span class="cerb-ui-pill">
		<span class="cerb-icons cerb-icon-circle-ok" style="color:var(--cerb-color-tag-green);"></span>
		<span>Draft saved <strong>{$timestamp|devblocks_date}</strong></span>
		<a data-cerb-link="hide" class="cerb-u-text-muted cerb-u-cursor-pointer" title="{'common.hide'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-circle-remove"></span></a>
	</span>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $div = $('#{$div_id}');
	$div.find('[data-cerb-link=hide]').on('click', function(e) {
		e.stopPropagation();
		$div.remove();
	});
});
</script>