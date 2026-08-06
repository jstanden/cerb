<div class="cerb-ui-form--field">
	<input type="text" name="{$namePrefix}[subroutine]" value="{$params.subroutine}">
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $action = $('#{$namePrefix}_{$nonce}');
	const $input = $action.find('input');

	if(window.CerbUI && CerbUI.TextChooser) {
		new CerbUI.TextChooser($input, {
			source: [
				{foreach from=$subroutines item=subroutine name=subs}
				'{$subroutine->title}'{if !$smarty.foreach.subs.last},{/if}
				{/foreach}
			],
			minLength: 0
		});
	}
});
</script>