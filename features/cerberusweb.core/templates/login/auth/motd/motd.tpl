<style>
#cerbLoginMotdForm {
	h1 {
		font-size: 2em;
	}

	h1, h2, h3, h4, h5, h6 {
		margin: 0.5em 0;
		color: var(--cerb-color-text);
	}
}
</style>

<div class="cerb-login-bg">
<form action="{devblocks_url}c=login&a=motd{/devblocks_url}" method="post" id="cerbLoginMotdForm">
<input type="hidden" name="accept" value="1">
<input type="hidden" name="_csrf_token" value="{$csrf_token}">

<div class="cerb-login-card" style="max-width:900px;">
	<div class="cerb-login-brand">
		<a href="{devblocks_url}{/devblocks_url}" tabindex="-1"><div id="cerb-logo"></div></a>
	</div>

	<div style="margin-bottom:24px;">
		{$motd_message nofilter}
	</div>

	<button type="button" class="submit cerb-login-submit">
		<span>
			{if $motd_button}
				{$motd_button}
			{else}
				{'common.continue'|devblocks_translate|capitalize}
			{/if}
		</span>
		<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
	</button>
</div>
</form>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#cerbLoginMotdForm');
	let $submit = $frm.find('.submit').attr('disabled', null);

	Devblocks.formDisableSubmit($frm);

	$submit.on('click', function(e) {
		e.stopPropagation();
		$frm[0].onsubmit = null;
		$submit.attr('disabled', 'disabled');
		$frm.submit();
	});
});
</script>
