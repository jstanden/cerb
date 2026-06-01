<fieldset style="margin-top:10px;position:relative;">
	<span class="cerb-icons cerb-icon-circle-remove"></span>
	<legend>{'common.preview'|devblocks_translate|capitalize}</legend>

	<div>
		{if !$toolbar}
			No interactions are available.
		{else}
			{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
		{/if}
	</div>
</fieldset>

{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
	let $script = $('#{$script_uid}');
	let $fieldset = $script.prev('fieldset');

	// Remove
	$fieldset.find('.cerb-icon-circle-remove')
		.css('position','absolute')
		.css('right','-5px')
		.css('top','-10px')
		.css('cursor','pointer')
		.css('color','rgb(80,80,80)')
		.css('zoom','1.5')
		.on('click', function(e) {
			e.stopPropagation();
			$(this).closest('fieldset').remove();
		})
	;

	// Menus
	$fieldset
		.find('button[data-cerb-toolbar-menu]')
		.on('click', function() {
			var $this = $(this);
			var $ul = $(this).next('ul').toggle();

			$ul.position({
				my: 'left top',
				at: 'left bottom',
				of: $this,
				collision: 'fit'
			});
		})
		.next('ul.cerb-float')
		.menu()
		.find('li.cerb-bot-trigger')
		.on('click', function(e) {
			e.stopPropagation();
			$(this).closest('ul.cerb-float').hide();
		})
	;
});
</script>
