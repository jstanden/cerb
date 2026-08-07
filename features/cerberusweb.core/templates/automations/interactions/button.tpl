<div id="bot-chat-button" class="cerb-no-print">
	{if DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')}
	<div class="bot-chat-icon-badge" {if !$proactive_interactions_count}style="display:none;"{/if}><span class="cerb-icons cerb-icon-bot-message"></span></div>
	{/if}
	<div class="bot-chat-icon"></div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $interaction_container = $('#bot-chat-button');
	let $interaction_button = $interaction_container.find('> div.bot-chat-icon');
	let $interaction_badge = $interaction_container.find('> div.bot-chat-icon-badge');
	let commandBarDialog = null;

	{if DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')}
	$interaction_badge.click(function(e) {
		e.stopPropagation();

		Devblocks.playAudioUrl('');

		genericAjaxGet(null, 'c=profiles&a=invoke&module=bot&action=getProactiveInteractions', function(json) {
			if(false === json || undefined === json.interaction) {
				$interaction_badge.hide();
				$interaction_button.click();

			} else {
				// Trigger the behavior
				let $target = $('<a/>')
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
	{/if}

	// Reused by every command-bar row: run the interaction's post-actions on a clean 'return'.
	let interactionDone = function(e) {
		if('object' !== typeof e || !e.hasOwnProperty('eventData'))
			return;

		if(!e.trigger.is('.cerb-bot-trigger'))
			return;

		if(e.eventData.exit === 'error') {

		} else if(e.eventData.exit === 'return') {
			Devblocks.interactionWorkerPostActions(e.eventData);
		}
	};

	// Build a tile row from the source data-* (CerbUI.Menu mirrors only data-*, not child nodes): a leading icon
	// tile (or bot avatar), a title + optional subtitle text column, and a trailing cluster of key caps.
	let renderCommandItem = function(rendered, source) {
		let ds = source.dataset;

		// Leading icon tile (the menu's own .cerb-ui-menu--label is the title).
		if(ds.image || ds.icon) {
			let tile = document.createElement('span');
			tile.className = 'cerb-command-bar--icon';

			if(ds.image) {
				let img = document.createElement('img');
				img.className = 'cerb-avatar';
				img.src = ds.image;
				tile.appendChild(img);
			} else {
				let icon = document.createElement('span');
				icon.className = 'cerb-icons cerb-icon-' + ds.icon;
				icon.setAttribute('aria-hidden', 'true');
				tile.appendChild(icon);
			}

			rendered.insertBefore(tile, rendered.firstChild);
		}

		// Wrap the title in a text column so an optional subtitle can stack beneath it.
		let label = rendered.querySelector('.cerb-ui-menu--label');

		if(label) {
			let text = document.createElement('span');
			text.className = 'cerb-command-bar--text';
			rendered.insertBefore(text, label);
			text.appendChild(label);

			if(ds.subtitle) {
				let subtitle = document.createElement('span');
				subtitle.className = 'cerb-command-bar--subtitle';
				subtitle.textContent = ds.subtitle;
				text.appendChild(subtitle);
			}
		}

		// Trailing keyboard-shortcut key caps (e.g. "C T").
		if(ds.keyboard) {
			let keys = document.createElement('span');
			keys.className = 'cerb-command-bar--keys';

			ds.keyboard.trim().split(/\s+/).forEach(function(k) {
				let kbd = document.createElement('span');
				kbd.className = 'cerb-ui-kbd';
				kbd.textContent = k;
				keys.appendChild(kbd);
			});

			rendered.appendChild(keys);
		}
	};

	let openCommandBar = function() {
		// Toggle: the shortcut/button re-press closes an open bar.
		if(commandBarDialog && commandBarDialog.isOpen()) {
			commandBarDialog.close();
			return;
		}

		// Fetch the menu HTML with NO target div, so genericAjaxGet skips its fade-in animation (which caused a
		// visible blink); build everything detached, then open the dialog already populated — a single paint.
		genericAjaxGet(null, 'c=profiles&a=invoke&module=automation&action=getInteractionsMenu', function(html) {
			let $content = $('<div class="cerb-command-bar"/>').html(html);
			let content = $content[0];
			let $ul = $content.find('ul.cerb-bot-interactions-menu');

			if(!$ul.length)
				return;

			// Hide the raw source list (CerbUI.Menu still parses it); bind the interaction trigger on the source
			// rows so selecting a menu row clicks its source <li>.
			$ul[0].hidden = true;

			$ul.find('li.cerb-bot-trigger').cerbBotTrigger({
				'caller': { 'name': 'cerb.toolbar.global.menu', 'params': {} },
				'done': interactionDone
			});

			// Resume rows reopen an existing continuation instead of starting a new interaction. If that
			// continuation is already open in a dialog, focus it rather than cloning a second copy.
			$ul.find('li.cerb-bot-resume-trigger').on('click', function(e) {
				e.stopPropagation();

				let token = this.dataset.continuationToken;

				if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog.focusByInput('continuation_token', token))
					return;

				Devblocks.resumeInteraction(token, { 'label': $(this).text().trim(), 'done': interactionDone });
			});

			// Build the menu while detached so the dialog opens fully populated (no empty-then-filled flash).
			let commandBarMenu = new CerbUI.Menu($ul[0], {
				inline: true,
				fixed: true,
				filter: true,
				filterPlaceholder: 'Run command...',
				filterAlways: true,
				filterIcon: 'search', // leading search icon inside the filter box
				clearActiveOnLeave: true, // hovering a row then moving off un-highlights it
				virtThreshold: 1000, // non-uniform tile rows: don't virtualize (global menu is small anyway)
				itemHeight: 44,
				maxHeight: 480,
				panelClass: 'cerb-command-bar-menu', // tile styling reaches floating submenus (appended to <body>)
				onRenderItem: renderCommandItem,
				onSelect: function(rendered, source) {
					$(source).click();
					if(commandBarDialog)
						commandBarDialog.close();
				}
			});

			commandBarDialog = new CerbUI.Dialog(content, {
				header: 'none',
				modal: true,
				fixed: true,
				width: 640,
				namespace: 'global-command-bar',
				closeOnBackdrop: true // clicking outside (the dimmed backdrop) closes the bar
			});

			// Throwaway popup: tear the menu (drops its document listeners) and DOM down on close.
			content.addEventListener('cerb-ui-dialog:close', function() {
				commandBarMenu.destroy();
				commandBarDialog.destroy();
			}, { once: true });

			commandBarDialog.open();

			// filterAlways couldn't focus while detached — focus the search box now that it's in the document.
			let filter = content.querySelector('.cerb-ui-menu--filter');
			if(filter)
				filter.focus();
		});
	};

	$interaction_button.on('click', function(e) {
		e.stopPropagation();

		Devblocks.playAudioUrl('');

		if($interaction_badge.is(':visible')) {
			$interaction_badge.click();
			return;
		}

		openCommandBar();
	});

	{if $pref_keyboard_shortcuts}
	$(document).keyup(function(e) {
		if(!(222 === e.which && e.shiftKey))
			return;

		let $target = $(e.target);

		if(!$target.is('BODY'))
			return;

		e.preventDefault();
		e.stopPropagation();

		$interaction_button.click();
	});
	{/if}
});
</script>