{$div_id = uniqid('div')}
<div id="{$div_id}" style="text-align:right;">
	<span class="glyphicons glyphicons-circle-ok"></span>
	Draft saved <strong>{$timestamp|devblocks_date}</strong> 
	(<a>{'common.hide'|devblocks_translate|lower}</a>)
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $div = $('#{$div_id}');
	$div.find('a').on('click', function(e) {
		e.stopPropagation();
		$div.remove();
	});
});
</script>