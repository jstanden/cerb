<div id="bot-chat-button" class="cerb-no-print">
	<div class="bot-chat-icon-badge" {if !$proactive_interactions_count}style="display:none;"{/if}><span class="glyphicons glyphicons-chat"></span></div>
	<div class="bot-chat-icon"></div>
	<div class="bot-chat-menu"></div>
</div>

<script type="text/javascript">
$(function() {
	var $interaction_container = $('#bot-chat-button');
	var $interaction_button = $interaction_container.find('> div.bot-chat-icon');
	var $interaction_menu = $interaction_container.find('> div.bot-chat-menu');
	var $interaction_badge = $interaction_container.find('> div.bot-chat-icon-badge');
	var $menu = null;
	
	$interaction_badge.click(function(e) {
		e.stopPropagation();
		
		Devblocks.playAudioUrl('');
		
		genericAjaxGet(null, 'c=profiles&a=invoke&module=bot&action=getProactiveInteractions', function(json) {
			if(false === json || undefined === json.interaction) {
				$interaction_badge.hide();
				$interaction_button.click();
				
			} else {
				// Trigger the behavior
				var $target = $('<a/>')
					.attr('href', 'javascript:;')
					.attr('data-behavior-id', json.behavior_id)
					.attr('data-interaction', json.interaction)
					.attr('data-interaction-params', $.param(json.interaction_params))
					;
				
				$target
					.cerbBotTrigger()
					.click()
					;
				
				if(json.finished) {
					$interaction_badge.hide();
				}
			}
		});
	});
	
	$interaction_button.on('click', function(e) {
		e.stopPropagation();
		
		Devblocks.playAudioUrl('');
		
		if($interaction_badge.is(':visible')) {
			$interaction_badge.click();
			return;
		}

		if(null == $menu) {
			Devblocks.getSpinner().css('max-width', '16px').appendTo($interaction_menu);
			
			genericAjaxGet($interaction_menu, 'c=profiles&a=invoke&module=bot&action=getInteractionsMenu', function(html) {
				$menu = $interaction_menu.find('> ul');
				
				$menu
					.menu({
						position: { my: "right middle", at: "left middle", collision: "fit" },
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
				
				$interaction_menu.show();
				$interaction_menu.addClass('bot-grab-menu');
				$menu.menu('focus', null, $menu.find('.ui-menu-item:first')).focus();
				
				$menu.find('li.cerb-bot-trigger').on('click', function(e) {
					e.stopPropagation();
					$interaction_menu.removeClass('bot-grab-menu');
					$menu.remove();
					$menu = null;
				});
			});
		
		} else {
			$interaction_menu.removeClass('bot-grab-menu');
			$menu.remove();
			$menu = null;
		}
	});
	
	{if $pref_keyboard_shortcuts}
	$(document).keyup(function(e) {
		if(!(222 === e.which && e.shiftKey))
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