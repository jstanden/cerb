<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html {if $pref_dark_mode}class="dark"{/if}>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
		<meta http-equiv="Cache-Control" content="no-cache">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		<meta name="color-scheme" content="{if $pref_dark_mode}dark{else}light{/if}">

		<meta name="robots" content="noindex">
		<meta name="googlebot" content="noindex">
		<meta name="_csrf_token" content="{$session.csrf_token}">
		
		<title>{$title}</title>
		{$favicon_url = DevblocksPlatform::getPluginSetting('cerberusweb.core','helpdesk_favicon_url','')}
		{if empty($favicon_url)}
		<link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
		{else}
		<link type="image/x-icon" rel="shortcut icon" href="{$favicon_url}">
		{/if}

		<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
			let DevblocksAppPath = '{$smarty.const.DEVBLOCKS_WEBPATH}';
			let DevblocksWebPath = '{devblocks_url}{/devblocks_url}';
			let DevblocksRequestNonce = '{DevblocksPlatform::getRequestNonce()}';
			let CerbSchemaRecordsVersion = {intval(DevblocksPlatform::services()->cache()->getTagVersion("schema_records"))};
		</script>

		<!-- Platform -->
		<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>
		<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=devblocks.core&f=js/devblocks.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

		<!-- Application -->
		<link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=cerberusweb.core&f=css/cerb.css{/devblocks_url}?v={$smarty.const.APP_BUILD}&pl=0">
		<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/cerberus.js{/devblocks_url}?v={$smarty.const.APP_BUILD}&pl=0"></script>
		<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" src="{devblocks_url}c=resource&p=cerberusweb.core&f=js/cerb-ui.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

		<style type="text/css">
			BODY { margin:0; padding:0; }
			IFRAME { width:100%; height:100%; border: 0; }
			{if $pref_dark_mode}
			HTML, BODY, IFRAME { background-color: rgb(32,32,32); }
			/* Hide the frame until its inner document has painted, so the dark page
			   background shows through instead of the browser's white loading canvas. */
			#explorerFrame { visibility: hidden; }
			{/if}
		</style>
	</head>
	
	<body>
		<table cellpadding="0" cellspacing="0" border="0" style="height:100vh;width:100vw;">
			<tr>
				<td style="height:50px;">
					<div class="block">
						<div style="display:flex;flex-flow:row wrap;">
							<div style="flex:1 1 auto;">
								<div style="display:flex;flex-flow:row wrap;">
									<div style="flex:0 0 60px;">
										<a href="{if !empty($return_url)}{$return_url}{else}{devblocks_url}{/devblocks_url}{/if}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/wgm/cerby.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" border="0" height="40"></span></a>
									</div>
									<div style="flex:1 1 auto;">
										<b style="font-size:1.5em;margin-right:5px;">{$title|default:"Results"|trim}</b>
										
										<div style="max-width:75vw;text-overflow:ellipsis;word-wrap:break-word;word-break:break-all;">
											{if !empty($content)}
											{$content}
											{else}
											<a href="{$url}" target="_blank" rel="noopener">{$url|truncate:100}</a>
											{/if}
										</div>
									</div>
								</div>
							</div>
							<div style="flex:1 1 auto;text-align:right;">
								<form id="explorerForm" action="{devblocks_url}c=explore&hash={$hashset}{/devblocks_url}" method="post">
									<input type="hidden" name="page" value="">
									<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
									{if $toolbar}
									<div id="explorerToolbar" style="display:inline-block;margin-right:1em;">
										{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
									</div>
									{/if}
								</form>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td>
					<iframe id="explorerFrame" src="about:blank" style="border:0;"></iframe>
				</td>
			</tr>
		</table>
	</body>

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		let $explorerBody = $('body');
		let $explorerForm = $('#explorerForm');
		let $explorerFrame = $('#explorerFrame');
		let $explorerToolbar = $('#explorerToolbar');

		let frameInit = function() {
			try {
				// Frame keyboard shortcuts
				let explorer_toolbar_ul = $explorerToolbar.find('ul.cerb-ui-toolbar')[0];
				if(explorer_toolbar_ul && window.CerbUI && CerbUI.Toolbar)
				new CerbUI.Toolbar(explorer_toolbar_ul, {
					caller: {
						name: 'cerb.toolbar.interaction.worker.explore',
						params: {
							'explore_hash': '{$hashset}',
							'explore_url': '{$url}',
						}
					},
					start: function (formData) {
					},
					done: function (e) {
						e.stopPropagation();
						let $target = e.trigger;

						if($target.is('.cerb-bot-trigger')) {
							if (e.eventData.exit === 'error') {

							} else if (e.eventData.exit === 'return') {
								Devblocks.interactionWorkerPostActions(e.eventData);

								// A returned `callout` targets the tour page inside the iframe, not the
								// explorer chrome. Re-run it in the frame's own Devblocks so the selector
								// resolves against that document and the tooltip is positioned there.
								try {
									let frameWin = $explorerFrame[0].contentWindow;
									if(
										frameWin && frameWin.Devblocks
										&& e.eventData.hasOwnProperty('return')
										&& e.eventData.return.hasOwnProperty('callout')
									) {
										frameWin.Devblocks.interactionWorkerPostActions({ return: { callout: e.eventData.return.callout } });
									}
								} catch(err) { if(console && console.error) console.error(err); }

								if(e.eventData.hasOwnProperty('return') && e.eventData.return.hasOwnProperty('explore_page')) {
									$explorerForm.find('input[name=page]').val(e.eventData.return.explore_page);
									$explorerForm.submit();
								}
							}

							let done_params = new URLSearchParams($target.attr('data-interaction-done'));

							// Default explore paging if the interaction doesn't return one
							if (done_params.has('explore_page')) {
								$explorerForm.find('input[name=page]').val(done_params.get('explore_page'));
								$explorerForm.submit();
								return;
							}

							let $profile_layout = $explorerFrame[0].contentWindow.$('.cerb-profile-layout');

							if($profile_layout.length > 0) {
								let done_actions = Devblocks.toolbarAfterActions(done_params, {
									'widgets':  $profile_layout.find('.cerb-profile-widget'),
									'default_widget_ids': false
								});

								// Refresh profile widgets
								if(
									done_actions.hasOwnProperty('refresh_widget_ids')
									&& false !== done_actions['refresh_widget_ids']
								) {
									$profile_layout.trigger(
										$.Event('cerb-widgets-refresh', {
											widget_ids: done_actions['refresh_widget_ids'],
											refresh_options: { }
										})
									);
								}
							}
						}
					}
				});
			} catch(e) { if(console && console.error) console.error(e); }
		}

		let funcOnLoad = function(e) {
			e.stopPropagation();

			// The inner document has painted; reveal the frame (no-op in light mode).
			$explorerFrame.css('visibility', 'visible');

			try {
				let $explorerFrameBody = $explorerFrame.contents().find('body').parent();
				let $responders = $explorerToolbar.find('[data-interaction-keyboard]');
				let onKeyShortcut = function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(this).click();
				};

				$responders.each(function() {
					let $this = $(this);
					$explorerBody.on(
						'keydown.exploreMode',
						null,
						$this.attr('data-interaction-keyboard'),
						onKeyShortcut.bind($this)
					);
					$explorerFrameBody.on(
						'keydown.exploreMode',
						null,
						$this.attr('data-interaction-keyboard'),
						onKeyShortcut.bind($this)
					);
				});

				$explorerFrame.focus();
			} catch(e) { if(console && console.error) console.error(e); }
		};

		frameInit();

		// Load the URL after we bind the `load` event
		$explorerFrame.get(0).addEventListener('load', funcOnLoad);
		$explorerFrame.attr('src', '{$url}');
	});
	</script>
</html>
