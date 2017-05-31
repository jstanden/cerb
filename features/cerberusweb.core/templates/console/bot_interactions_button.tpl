<div id="bot-chat-button">
	<div class="bot-chat-icon">
		<img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}">
	</div>
	<div class="bot-chat-menu"></div>
</div>

<script type="text/javascript">
$(function() {
	var $interaction_container = $('#bot-chat-button');
	var $interaction_button = $interaction_container.find('> div.bot-chat-icon');
	var $interaction_menu = $interaction_container.find('> div.bot-chat-menu');
	var $menu = null;
	
	$interaction_button.on('click', function(e) {
		Devblocks.playAudioUrl('');
		
		if(null == $menu) {
			var $spinner = $('<span class="cerb-ajax-spinner" style="zoom:0.5;"></span>')
				.appendTo($interaction_menu)
			;
			
			genericAjaxGet($interaction_menu, 'c=internal&a=getBotInteractionsMenu', function(html) {
				$menu = $interaction_menu.find('> ul');
				
				$menu
					.menu({
						position: { my: "right middle", at: "left middle" },
						select: function(event, ui) {
							event.stopPropagation();
							$(ui.item).click();
						}
					})
					.css('position', 'absolute')
					.css('right', '0')
					.css('bottom', '50px')
				;
				
				$menu.find('li.cerb-bot-trigger')
					.cerbBotTrigger()
					.on('click', function(e) {
						e.stopPropagation();
						$menu.menu("collapse");
					});
				
				$interaction_menu.fadeIn();
				$menu.menu('focus', null, $menu.find('.ui-menu-item:first')).focus();
			});
		} else {
			$interaction_menu.toggle();
			
			if($menu.is(':visible'))
				$menu.menu('focus', null, $menu.find('.ui-menu-item:first')).focus();
		}
	});
	
	{if $pref_keyboard_shortcuts}
	$(document).keyup(function(e) {
		if(!(222 == e.which && e.shiftKey))
			return;
		
		var $target = $(e.target);
		
		if(!$target.is('BODY, UL.cerb-bot-interactions-menu'))
			return;
		
		e.preventDefault();
		e.stopPropagation();
		
		$interaction_button.click();
	});
	{/if}
});
</script>