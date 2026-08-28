{*
 * WHERE the worker is, stamped at PAGE-render time.
 *
 * `$response_uri` is the ROUTED path Devblocks resolved (`profiles/ticket/1234`), not the browser's address
 * bar -- which is the point, since a controller can render something the URL doesn't describe.
 *
 * It has to be captured HERE. The command bar's menu and its interaction launches are both AJAX posts to
 * `c=profiles&a=invoke`, so reading DevblocksPlatform::getHttpResponse() at either of those moments returns
 * that endpoint rather than the page -- plausible-looking, and always the same wrong answer. This template
 * renders inside the page (footer.tpl <- border.tpl), so it is the one place in this path that knows.
 *}
<div id="bot-chat-button" class="cerb-no-print" data-page-uri="{$response_uri|default:''}"{if !empty($page)} data-page-title="{$page->manifest->name|default:''}" data-page-id="{$page->manifest->id|default:''}"{/if}>
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

			// A row carrying its own color (a resumed conversation wearing its provider's brand mark) fills the
			// tile with it, matching how the agent pane draws the same conversation. Inline, so it also survives
			// the accent-filled active row.
			//
			// Always a WHITE glyph, never a contrast-picked one: brand marks are drawn white on their own color
			// everywhere else (`_avatar.scss` does exactly this), so computing per-color legibility here would
			// make the same conversation look different in the pane and the command bar.
			if(ds.iconColor && !ds.image) {
				tile.style.backgroundColor = ds.iconColor;
				tile.style.color = '#fff';
			}

			// A row with BOTH is a conversation with an agent: the agent's face is the tile, and the model's
			// mark demotes to a corner badge rather than being dropped. Without this the picture simply replaced
			// the mark and every History row looked the same as every other -- they all ran the same model, and
			// the agent is the part worth telling apart. Same split the agent pane and the transcript make.
			if(ds.image && ds.icon) {
				tile.classList.add('cerb-avatar-badged');

				let badge = document.createElement('span');
				badge.className = 'cerb-ui-pill cerb-ui-pill--circle cerb-command-bar--icon-badge';

				if(ds.iconColor) {
					badge.style.setProperty('--cerb-ui-pill-color', ds.iconColor);
					badge.style.color = '#fff';
				}

				let mark = document.createElement('span');
				mark.className = 'cerb-icons cerb-icon-' + ds.icon;
				mark.setAttribute('aria-hidden', 'true');
				badge.appendChild(mark);

				tile.appendChild(badge);
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

			// One subtitle line, up to two parts: what the row is about, then a dimmer trailing note (how long a
			// conversation has been idle). Kept on one line rather than stacked -- a three-line row makes the
			// palette scroll instead of scan.
			if(ds.subtitle || ds.meta) {
				let subtitle = document.createElement('span');
				subtitle.className = 'cerb-command-bar--subtitle';

				// A real element, not a bare text node: the meta separator keys off `:not(:first-child)`, and CSS
				// child matching ignores text nodes -- so an unwrapped string leaves meta as the first ELEMENT
				// child and the two run together ("Agent Chat9 secs ago").
				if(ds.subtitle) {
					let text = document.createElement('span');
					text.textContent = ds.subtitle;
					subtitle.appendChild(text);
				}

				if(ds.meta) {
					let meta = document.createElement('span');
					meta.className = 'cerb-command-bar--meta';
					meta.textContent = ds.meta;
					subtitle.appendChild(meta);
				}

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

			// The command bar's agent-pane bridge. It is the `commandbar` component, so an
			// `interaction.worker.agent` chat launched here gets that component's tools automatically and drives
			// them back through this callback. Kept in sync with Cerb\Agent\Pane\Components (a command
			// catalogued there but missing here returns '' at runtime, which reads as a model failure).
			//
			// Unlike the six editor panes there is no document to read or write -- the command bar acts on the
			// APP. Each command returns synchronously; a promise would lose the race with the await's auto-submit.
			let commandBarRunCommand = function(name, params) {
				params = params || {};

				if('getPage' === name) {
					// Read from the BUTTON's data attributes, stamped during the page render. Read live on every
					// call, so the answer is still right after the worker navigates and resumes here.
					let open = [];

					if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog._openDialogs) {
						CerbUI.Dialog._openDialogs.forEach(function(d) {
							if(!d || !d._open) return;
							open.push({
								title: (d.titleEl && d.titleEl.textContent || '').trim(),
								minimized: !!d.minimized
							});
						});
					}

					// Stringified because a uiCommand result is coerced through String() -- an object would
					// arrive as "[object Object]".
					return JSON.stringify({
						page_uri: $interaction_container.attr('data-page-uri') || '',
						page_title: $interaction_container.attr('data-page-title') || '',
						page_id: $interaction_container.attr('data-page-id') || '',
						url: window.location.href,
						open_popups: open
					});
				}

				if('openSearch' === name) {
					let recordType = String(params.record_type || '').trim();

					if(!recordType)
						return 'ERROR: record_type is required.';

					// Same mechanism `return:search:` uses (Devblocks.interactionWorkerPostActions), but
					// mid-conversation rather than as the interaction's last act. The query is optional --
					// cerbSearchTrigger skips an empty one and opens an unfiltered search.
					$('<div/>')
						.attr('data-context', recordType)
						.attr('data-query', String(params.record_query || ''))
						.cerbSearchTrigger()
						.on('cerb-search-opened', function(e) {
							e.stopPropagation();
							$(this).remove();
						})
						.click()
					;

					// The popup loads over AJAX and we can't see whether the record type resolved, so this
					// reports what was ASKED for. An unknown type opens nothing at all.
					return 'Opened a search popup for `' + recordType + '`.';
				}

				return '';
			};

			// Page context is deliberately NOT here. It would be a snapshot from launch, and the obvious use for
			// it -- naming the page in the system prompt -- is the one place it must never go: the system prompt
			// is the most stable part of the cached prompt prefix, and `_persistSessionConfig()` rewrites it from
			// the freshly-evaluated input every turn, so a value that ever changes invalidates the whole prefix.
			// `cerb_get_page` reads it live instead, which is both cache-safe and correct after the worker moves.
			let commandBarCaller = {
				'name': 'cerb.toolbar.global.menu',
				'params': {
					'component': 'commandbar',
					'ui_capabilities': 'openSearch,getPage'
				}
			};

			$ul.find('li.cerb-bot-trigger').cerbBotTrigger({
				'caller': commandBarCaller,
				'command': commandBarRunCommand,
				'done': interactionDone
			});

			// Resume rows reopen an existing continuation instead of starting a new interaction. If that
			// continuation is already open in a dialog, focus it rather than cloning a second copy.
			$ul.find('li.cerb-bot-resume-trigger').on('click', function(e) {
				e.stopPropagation();

				let token = this.dataset.continuationToken;

				if(window.CerbUI && CerbUI.Dialog && CerbUI.Dialog.focusByInput('continuation_token', token))
					return;

				// The SAME caller the launch path posts above. The server re-derives `resume_scope` from it and
				// refuses a mismatch, so omitting it resolved to '' and every reopen failed with "That
				// conversation belongs to a different workspace." The `command` bridge has to come along too, or
				// a resumed chat's uiCommands go quiet even though they worked on the first turn.
				Devblocks.resumeInteraction(token, {
					'label': $(this).text().trim(),
					'caller': commandBarCaller,
					'command': commandBarRunCommand,
					'done': interactionDone
				});
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
				// Caps the LIST, not the whole bar -- the filter row and the dialog's own padding sit on top of
				// it, which is what the reserved 180px is for. The bar is a centered MODAL that nothing else has
				// to share the screen with, so it takes the viewport it's given rather than a fixed 480px that
				// scrolled a dozen rows out of sight on a tall display. Floored so a short window behaves as it
				// always did. Recomputed per open: the whole popup is rebuilt each time (see openCommandBar).
				maxHeight: Math.max(480, window.innerHeight - 180),
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