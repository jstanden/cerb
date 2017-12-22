<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[subroutine]" value="{$params.subroutine}" style="width:100%;">
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $input = $action.find('input');
	
	$input.autocomplete({
		delay: 300,
		source: [
			{foreach from=$subroutines item=subroutine name=subs}
			'{$subroutine->title}'{if !$smarty.foreach.subs.last},{/if}
			{/foreach}
		],
		minLength: 0,
		autoFocus:true
	});
});
</script>