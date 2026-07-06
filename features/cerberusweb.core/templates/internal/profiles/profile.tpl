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

<div style="float:left;margin-right:10px;">

	<span data-cerb-profile-avatar
		class="cerb-ui-avatar cerb-ui-avatar--tile"
		data-avatar="{$dict->_label|escape}"
		{* data-avatar-seed="{$page_context}:{$page_context_id}" *}
	  	data-avatar-color="var(--cerb-color-background-contrast-180)"
		data-avatar-size="75"
		{if $context_ext->hasOption('avatars')}data-avatar-image="{devblocks_url}c=avatars&context={$page_record_uri}&context_id={$page_context_id}{/devblocks_url}?v={$dict->updated_at|default:$dict->updated|default:$dict->updated_date}"{else}data-avatar-icon="{$context_ext->getIcon()}"{/if}
	></span>
</div>

<div class="cerb-ui-header cerb-ui-header--tight">
	<div>
		<div class="cerb-ui-header--subtitle">{$context_ext->manifest->name}</div>
		<div class="cerb-ui-header--title">{$dict->_label}</div>
	</div>
</div>

<div id="profileToolbar" style="float:left;">
	<div class="cerb-profile-toolbar cerb-no-print">
		<form class="toolbar" action="{devblocks_url}{/devblocks_url}" method="post" style="margin-bottom:5px;">
			<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

			{if !is_array($toolbar_profile) || !array_key_exists('card', $toolbar_profile)}
				<button type="button" id="btnProfileCard" title="{'common.card'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (V){/if}" data-context="{$page_context}" data-context-id="{$page_context_id}"><span class="cerb-icons cerb-icon-id-card"></span> {'common.card'|devblocks_translate|capitalize}</button>
			{/if}

			{if !is_array($toolbar_profile) || !array_key_exists('edit', $toolbar_profile)}
				{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.update")}
				<button type="button" id="btnProfileCardEdit" title="{'common.edit'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (E){/if}" class="cerb-peek-trigger" data-context="{$page_context}" data-context-id="{$page_context_id}" data-width="75%" data-edit="true"><span class="cerb-icons cerb-icon-gear"></span> {'common.edit'|devblocks_translate|capitalize}</button>
				{/if}
			{/if}

			{if !is_array($toolbar_profile) || !array_key_exists('comments', $toolbar_profile)}
				{if $context_ext->hasOption('comments') && $active_worker->hasPriv("contexts.{$page_context}.comment")}
				<button type="button" id="btnProfileComment" title="(O)" data-context="cerberusweb.contexts.comment" data-context-id="0" data-edit="context:{$page_context} context.id:{$page_context_id}">
					<span class="cerb-icons cerb-icon-conversation"></span> {'common.comment'|devblocks_translate|capitalize}
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

            {if !is_array($toolbar_profile) || !array_key_exists('merge', $toolbar_profile)}
				{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.merge")}
					<button type="button" id="btnProfileMerge">
						<span class="cerb-icons cerb-icon-merge"></span> {'common.merge'|devblocks_translate|capitalize}
					</button>
				{/if}
			{/if}

			<div data-cerb-toolbar style="display:inline-block;">
				{if $toolbar_profile}
				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_profile)}
				{/if}
			</div>

			{if !is_array($toolbar_profile) || !array_key_exists('refresh', $toolbar_profile)}
				<button data-cerb-button-refresh type="button" title="{'common.refresh'|devblocks_translate|capitalize}"><span class="cerb-icons cerb-icon-refresh"></span></button>
			{/if}

			{if $active_worker->is_superuser}
				<button type="button" data-cerb-toolbar-setup class="cerb-ui-toolbar-config-button" title="{'common.configure'|devblocks_translate|capitalize}" data-context="{CerberusContexts::CONTEXT_TOOLBAR}" data-context-id="record.profile" data-edit="true"><span class="cerb-icons cerb-icon-gear"></span></button>
			{/if}
		</form>
	</div>
</div>

<div style="clear:both;padding-top:5px;"></div>

<div>
{include file="devblocks:cerberusweb.core::internal/notifications/context_profile.tpl" context=$page_context context_id=$page_context_id}
</div>

{if DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')}
<div>
{include file="devblocks:cerb.behaviors.legacy::internal/macros/behavior/scheduled_behavior_profile.tpl" context=$page_context context_id=$page_context_id}
</div>
{/if}

<div style="clear:both;">
	<ul id="{$tabset_id}">
		{$tabs = []}

		{$profile_tabs = DAO_ProfileTab::getByContext($page_context)}

		{foreach from=$profile_tabs item=profile_tab}
			{if !$profile_tab->isHidden($profile_dict)}
				{$tabs[] = "{$profile_tab->name|lower|devblocks_permalink}"}
				<li><a href="c=profiles&a=renderTab&tab_id={$profile_tab->id}&context={$page_context}&context_id={$page_context_id}" draggable="false">{$profile_tab->name}</a></li>
			{/if}
		{/foreach}

		{if $active_worker->is_superuser}
		<li class="cerb-no-print"><a href="c=profiles&a=configTabs&context={$page_context}&context_id={$page_context_id}">&nbsp;<span class="cerb-icons cerb-icon-gear"></span>&nbsp;</a></li>
		{/if}
	</ul>
</div>
<br>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	// Profile header avatar
	if(window.CerbUI && CerbUI.Avatar) {
		CerbUI.Avatar.enhance(document, '[data-cerb-profile-avatar]');
	}

	// Tabs
	const profileTabsUl = document.getElementById('{$tabset_id}');
	let cerbProfileTabs = null;

	if(profileTabsUl && window.CerbUI && CerbUI.Tabs) {
		let active; // undefined → use the remembered index / 0
		{if $tab_selected && in_array($tab_selected, $tabs)}
		active = {array_search($tab_selected, $tabs)};
		{/if}

		cerbProfileTabs = new CerbUI.Tabs(profileTabsUl, { remember: '{$tabset_id}', active: active });
	}

	// The active panel can change after init, and dynamic content loads async — look it up live
	const getProfileActivePanel = function() {
		return (cerbProfileTabs && cerbProfileTabs.activeTab) ? $(cerbProfileTabs.activeTab.panel) : $();
	};

	// Set the browser tab label to the record label
	document.title = "{$dict->_label|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
    let doneFunc = function(e) {
        e.stopPropagation();

        var $target = e.trigger;
        var $profile_tab = getProfileActivePanel();

        if(!$target.is('.cerb-bot-trigger'))
            return;

        if(e.eventData.exit === 'return') {
            Devblocks.interactionWorkerPostActions(e.eventData);
        }

        let done_params = new URLSearchParams($target.attr('data-interaction-done'));

        if(done_params.has('refresh_toolbar')) {
            let refresh = done_params.get('refresh_toolbar');

            if(refresh && '0' !== refresh) {
            	$toolbar.trigger($.Event('cerb-toolbar--refresh'));
			}
        }
        
        let done_actions = Devblocks.toolbarAfterActions(done_params, {
			'widgets': $profile_tab.find('.cerb-profile-widget'),
			'default_widget_ids': []
		});

        // Refresh profile widgets
        if(done_actions.hasOwnProperty('refresh_widget_ids')) {
			$profile_tab.find('.cerb-profile-layout').triggerHandler(
                $.Event('cerb-widgets-refresh', {
                	widget_ids: done_actions['refresh_widget_ids'],
                	refresh_options: { }
            	})
			);
		}
    };
    
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

	// Refresh
	$profile_toolbar.find('[data-cerb-button-refresh]').on('click', function(e) {
		e.stopPropagation();
		document.location.reload();
	});
	
	// Comments
	$('#btnProfileComment')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			e.stopPropagation();

			if(e.id && e.hasOwnProperty('comment_html') && e.comment_html) {
				var $tab_content = getProfileActivePanel();
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

    // Merge

	{if $is_writeable && $active_worker->hasPriv("contexts.{$page_context}.merge")}
    $('#btnProfileMerge')
        .on('click', function() {
            var $merge_popup = genericAjaxPopup('peek','c=internal&a=invoke&module=records&action=renderMergePopup&context={$page_context}&ids={$page_context_id}',null,false,'50%');

            $merge_popup.on('record_merged', function(e) {
                e.stopPropagation();
                document.location.reload();
            });
        })
    ;
    {/if}
	
	// Toolbar
	
	var $toolbar = $profile_toolbar.find('[data-cerb-toolbar]');
    
    $toolbar.on('cerb-toolbar--refresh', function(e) {
		e.stopPropagation();

        genericAjaxGet('', 'c=profiles&a=renderToolbar&record_type={$dict->_context}&record_id={$dict->id}&toolbar=record.profile', function(html) {
            $toolbar
                .html(html)
                .trigger('cerb-toolbar--refreshed')
            ;
        });
    });

	let buildProfileToolbar = function() {
	let profile_toolbar_ul = $toolbar.find('ul.cerb-ui-toolbar')[0];
	if(!profile_toolbar_ul || !(window.CerbUI && CerbUI.Toolbar)) return;
	new CerbUI.Toolbar(profile_toolbar_ul, {
		caller: {
			name: 'cerb.toolbar.record.profile',
			params: {
				'record__context': '{$dict->_context}',
				'record_id': '{$dict->id}'
			}
		},
		start: function(formData) {
		},
		done: doneFunc
	});
	};
	$toolbar.on('cerb-toolbar--refreshed', buildProfileToolbar);
	buildProfileToolbar();
	
	var $toolbar_setup = $profile_toolbar.find('[data-cerb-toolbar-setup]');

	$toolbar_setup
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

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{if $pref_keyboard_shortcuts}
$(function() {
	var $document = $(document);
	var $body = $document.find('body');
	const cerbProfileTabs = (window.CerbUI && CerbUI.Tabs) ? CerbUI.Tabs.from(document.getElementById('{$tabset_id}')) : null;

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
			if(cerbProfileTabs) cerbProfileTabs.select(idx);
		} catch(ex) { }
	});

	$document.bind('keydown', function(e) {
		if($(e.target).is(':input'))
			return;

		var $tab_content = (cerbProfileTabs && cerbProfileTabs.activeTab) ? $(cerbProfileTabs.activeTab.panel) : $();
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