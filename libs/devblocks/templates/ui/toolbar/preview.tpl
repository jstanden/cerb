<fieldset style="margin-top:10px;position:relative;">
	<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;" onclick="$(this).closest('fieldset').remove();"></span>
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
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $fieldset = $script.prev('fieldset');

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
