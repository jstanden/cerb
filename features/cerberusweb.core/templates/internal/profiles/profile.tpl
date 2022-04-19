{$page_context = $dict->_context}
{$page_context_id = $dict->id}
{$page_record_uri = $context_ext->manifest->params.alias}
{$is_writeable = CerberusContexts::isWriteableByActor($page_context, $record, $active_worker)}
{$tabset_id = "profile-tabs-{DevblocksPlatform::strAlphaNum($page_context,'','_')}"}

{if $smarty.const.APP_OPT_DEPRECATED_PROFILE_QUICK_SEARCH}
<div style="margin-bottom:5px;">
	{$ctx = Extension_DevblocksContext::get($page_context|default:'')}
	{if is_a($ctx, 'Extension_DevblocksContext')}
		{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$ctx->getSearchView() return_url="{devblocks_url}c=search&context={$page_record_uri}{/devblocks_url}"}
	{/if}
</div>
{/if}

{if $context_ext->hasOption('avatars')}
<div style="float:left;margin-right:10px;">
	<img src="{devblocks_url}c=avatars&context={$page_record_uri}&context_id={$page_context_id}{/devblocks_url}?v={$dict->updated_at|default:$dict->updated|default:$dict->updated_date}" style="height:75px;width:75px;border-radius:5px;">
</div>
{/if}

<div id="profileToolbar" style="float:left;">
	<h1 style="font-size:2em;">{$dict->_label}</h1>
	
	<div class="cerb-profile-toolbar cerb-no-print">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">
			
			{if !is_array($toolbar_profile) || !array_key_exists('card', $toolbar_profile)}
				<button type="button" id="btnProfileCard" title="{'common.card'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (V){/if}" data-context="{$page_context}" data-context-id="{$page_context_id}"><span class="glyphicons glyphicons-nameplate"></span> {'common.card'|devblocks_translate|capitalize}</button>
			{/if}
			
			{if !is_array($toolbar_profile) || !array_key_exists('edit', $toolbar_profile)}
				{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
				<button type="button" id="btnProfileCardEdit" title="{'common.edit'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (E){/if}" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-width="75%" data-edit="true"><span class="glyphicons glyphicons-cogwheel"></span> {'common.edit'|devblocks_translate|capitalize}</button>
				{/if}
			{/if}

			{if !is_array($toolbar_profile) || !array_key_exists('comments', $toolbar_profile)}
				{if $context_ext->hasOption('comments') && array_key_exists('comment', $context_ext->manifest->params.acl.0)}
				<button type="button" id="btnProfileComment" title="(O)" data-context="cerberusweb.contexts.comment" data-context-id="0" data-edit="context:{$page_context} context.id:{$page_context_id}">
					<span class="glyphicons glyphicons-conversation"></span> {'common.comment'|devblocks_translate|capitalize}
				</button>
				{/if}
			{/if}
			
			{if !is_array($toolbar_profile) || !array_key_exists('watchers', $toolbar_profile)}
				{if $context_ext->hasOption('watchers')}
					<span id="spanProfileWatchers" title="{'common.watchers'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (W){/if}">
					{$object_watchers = DAO_ContextLink::getContextLinks($page_context, array($page_context_id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context=$page_context context_id=$page_context_id full_label=true}
					</span>
				{/if}
			{/if}
			
			<div data-cerb-toolbar style="display:inline-block;">
				{if $toolbar_profile}
				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_profile)}
				{/if}
			</div>
			
			{if !is_array($toolbar_profile) || !array_key_exists('refresh', $toolbar_profile)}
				<button type="button" title="{'common.refresh'|devblocks_translate|capitalize}" onclick="document.location.reload();"><span class="glyphicons glyphicons-refresh"></span></button>
			{/if}
			
			{if $active_worker->is_superuser}
				<div data-cerb-toolbar-setup style="display:inline-block;vertical-align:middle;">
					<a href="javascript:" data-context="{CerberusContexts::CONTEXT_TOOLBAR}" data-context-id="record.profile" data-edit="true"><span class="glyphicons glyphicons-cogwheel" style="color:lightgray;"></span></a>
				</div>
			{/if}
		</form>
	</div>
</div>

<div style="clear:both;padding-top:5px;"></div>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div>
{include file="devblocks:cerberusweb.core::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

<div style="clear:both;" id="{$tabset_id}">
	<ul>
		{$tabs = []}
		
		{$profile_tabs = DAO_ProfileTab::getByProfile($page_context)}
		
		{foreach from=$profile_tabs item=profile_tab}
			{$tabs[] = "{$profile_tab->name|lower|devblocks_permalink}"}
			<li><a href="{devblocks_url}ajax.php?c=profiles&a=renderTab&tab_id={$profile_tab->id}&context={$page_context}&context_id={$page_context_id}{/devblocks_url}">{$profile_tab->name}</a></li>
		{/foreach}
		
		{if $active_worker->is_superuser}
		<li class="cerb-no-print"><a href="{devblocks_url}ajax.php?c=profiles&a=configTabs&context={$page_context}{/devblocks_url}">&nbsp;<span class="glyphicons glyphicons-cogwheel"></span>&nbsp;</a></li>
		{/if}
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	
	{if $tab_selected && in_array($tab_selected, $tabs)}
	{$tab_idx = array_search($tab_selected, $tabs)}
	tabOptions.active = {$tab_idx};
	Devblocks.setjQueryUiTabSelected('{$tabset_id}', {$tab_idx});
	{else}
	tabOptions.active = Devblocks.getjQueryUiTabSelected('{$tabset_id}');
	{/if}
	
	// Tabs
	var $tabs = $("#{$tabset_id}").tabs(tabOptions);
	var $profile_tab = $tabs.find('.ui-tabs-panel[aria-hidden=false]');
	
	// Set the browser tab label to the record label
	document.title = "{$dict->_label|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
	// Peeks
	$('#btnProfileCard').cerbPeekTrigger();
	
	// Edit
	
	$('#btnProfileCardEdit')
		.cerbPeekTrigger()
		.on('cerb-peek-opened', function(e) {
		})
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();
			// [TODO] Don't refresh the page, just send an event to the current tab

			if(e.hasOwnProperty('is_continue') && e.is_continue) {
				// Do nothing
			} else if(!e.is_rebroadcast) {
				document.location.reload();
			}
		})
		.on('cerb-peek-deleted', function(e) {
			if(!e.is_rebroadcast) {
				document.location.href = '{devblocks_url}{/devblocks_url}';
			}
		})
	;
	
	var $profile_toolbar = $('#profileToolbar');
	
	// Comments
	$('#btnProfileComment')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();

			if(e.id && e.hasOwnProperty('comment_html') && e.comment_html) {
				var $tab_content = $('#' + $tabs.find('li.ui-tabs-active').attr('aria-controls'));
				var $widgets = $tab_content.find('div.cerb-profile-widget');
				
				var event_new_comment = $.Event('cerb_profile_comment_created');
				event_new_comment.comment_id = e.id;
				event_new_comment.comment_html = e.comment_html;
				
				$widgets.each(function() {
					if(!event_new_comment.isPropagationStopped()) {
						var $widget = $(this);
						$widget.triggerHandler(event_new_comment);
					}
				});
			}
		})
		;

	// Toolbar
	
	var $toolbar = $profile_toolbar.find('[data-cerb-toolbar]');

	$toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.record.profile',
			params: {
				'record__context': '{$dict->_context}',
				'record_id': '{$dict->id}'
			}
		},
		start: function(formData) {
		},
		done: function(e) {
			e.stopPropagation();
			
			var $target = e.trigger;
			
			if(!$target.is('.cerb-bot-trigger'))
				return;

			var done_params = new URLSearchParams($target.attr('data-interaction-done'));
			
			// Refresh all widgets by default
			if(!done_params.has('refresh_widgets[]')) {
				done_params.set('refresh_widgets[]', 'all');
			}

			var refresh = done_params.getAll('refresh_widgets[]');
			var widget_ids = [];
			
			if(-1 !== $.inArray('all', refresh)) {
				// Everything
			} else {
				$profile_tab.find('.cerb-profile-widget')
					.filter(function() {
						var $this = $(this);
						var name = $this.attr('data-widget-name');

						if(undefined === name)
							return false;

						return -1 !== $.inArray(name, refresh);
					})
					.each(function() {
						var $this = $(this);
						var widget_id = parseInt($this.attr('data-widget-id'));

						if(widget_id)
							widget_ids.push(widget_id);
					})
				;
			}

			var evt = $.Event('cerb-widgets-refresh', {
				widget_ids: widget_ids,
				refresh_options: { }
			});
			
			$profile_tab.find('.cerb-profile-layout').triggerHandler(evt);			
		}
	});
	
	var $toolbar_setup = $profile_toolbar.find('[data-cerb-toolbar-setup]');
	
	$toolbar_setup.find('a')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			genericAjaxGet('', 'c=profiles&a=renderToolbar&record_type={$dict->_context}&record_id={$dict->id}&toolbar=record.profile', function(html) {
				$toolbar
					.html(html)
					.trigger('cerb-toolbar--refreshed')
				;
			});
		})
	;
});
</script>

<script type="text/javascript">
{if $pref_keyboard_shortcuts}
$(function() {
	var $document = $(document);
	var $body = $document.find('body');
	var $tabs = $("#{$tabset_id}").tabs();
	
	$body.bind('keypress', 'E', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#btnProfileCardEdit').click();
	});
	
	$body.bind('keypress', 'O', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#btnProfileComment').click();
	});
	
	$body.bind('keypress', 'V', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#btnProfileCard').click();
	});
	
	$body.bind('keypress', 'W', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#spanProfileWatchers button:first').click();
	});

	$body.bind('keypress', 'Shift+W', function(e) {
		e.preventDefault();
		e.stopPropagation();

		var event = new $.Event('click');
		event.shiftKey = true;

		$('#spanProfileWatchers button:first').triggerHandler(event);
	});

	$body.bind('keypress', '1 2 3 4 5 6 7 8 9 0', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		try {
			var idx = event.which-49;
			$tabs.tabs('option', 'active', idx);
		} catch(ex) { }
	});
	
	$document.bind('keydown', function(e) {
		if($(e.target).is(':input'))
			return;
		
		var $tab_content = $('#' + $tabs.find('li.ui-tabs-active').attr('aria-controls'));
		var $widgets = $tab_content.find('div.cerb-profile-widget');
		
		$widgets.each(function() {
			if(!e.isPropagationStopped()) {
				var $widget = $(this);
				$widget.triggerHandler(e);
			}
		});
	});
});
{/if}
</script>

{include file="devblocks:cerberusweb.core::internal/profiles/profile_common_scripts.tpl"}