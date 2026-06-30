<div id="cardWidget{$widget->getUniqueId($dict->id)}Fields">
	<div>
		{if $properties}
			<div class="cerb-fields-container">
			{foreach from=$properties item=v key=k name=props}
				<div class="cerb-fields-container-item">
					{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
				</div>
			{/foreach}
			</div>
		{/if}

		<div data-cerb-toolbar-container style="margin:5px 0 10px 5px;">
			{if $search_buttons}
				<ul class="cerb-ui-toolbar" data-cerb-search-buttons>
					{foreach from=$search_buttons item=search_button}
					<li class="cerb-search-trigger" data-context="{$search_button.context}" data-icon="{$search_button.icon|default:'collection'}" data-query="{$search_button.query}" data-badge="{$search_button.count|default:0}">{$search_button.label|capitalize}</li>
					{/foreach}
				</ul>
			{/if}

			{if $toolbar_fields}
				<div data-cerb-toolbar style="display:inline-block;">
					{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar_fields)}
				</div>
			{/if}
		</div>
	</div>
	
	{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/profile_fieldsets.tpl" properties=$properties_custom_fieldsets}
	
	{include file="devblocks:cerberusweb.core::internal/profiles/profile_record_links.tpl" properties=$properties_links}
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $widget = $('#cardWidget{$widget->getUniqueId($dict->id)}Fields');
	var $popup = genericAjaxPopupFind($widget);
	
	let $properties = $widget.find('> div:first, > fieldset.properties');
	$properties.find('.cerb-peek-trigger').cerbPeekTrigger();
	$properties.find('.cerb-search-trigger').cerbSearchTrigger();
	
	var $toolbar_container = $widget.find('[data-cerb-toolbar-container]');
	var $toolbar = $toolbar_container.find('[data-cerb-toolbar]');

	// Search buttons: a CerbUI.Toolbar. Counts render as a floating corner badge (default 'pill') rather
	// than a leading inline tally, so the icon + count don't crowd the left of each label. cerbSearchTrigger
	// (bound above on the source .cerb-search-trigger <li>s) still opens the search — onSelect clicks the row.
	let search_buttons_ul = $toolbar_container.find('[data-cerb-search-buttons]')[0];
	if(search_buttons_ul && window.CerbUI && CerbUI.Toolbar)
		new CerbUI.Toolbar(search_buttons_ul, {
			badgeStyle: 'pill',
			onSelect: function(item, sourceLi) {
				if(sourceLi)
					$(sourceLi).trigger('click');
			}
		});

	let toolbar_ul = $toolbar.find('ul.cerb-ui-toolbar')[0];
	if(toolbar_ul && window.CerbUI && CerbUI.Toolbar)
	new CerbUI.Toolbar(toolbar_ul, {
		caller: {
			name: 'cerb.toolbar.cardWidget.recordFields',
			params: {
				'record__context': '{$dict->_context}',
				'record_id': '{$dict->id}',
				
				'widget__context': '{CerberusContexts::CONTEXT_CARD_WIDGET}',
				'widget_id': '{$widget->id}',
				
				'worker__context': '{CerberusContexts::CONTEXT_WORKER}',
				'worker_id': '{$active_worker->id}'
			}
		},
		start: function(formData) {
			// Include any dynamic params
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if(!$target.is('.cerb-bot-trigger'))
				return;

			if (e.eventData.exit === 'error') {

			} else if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData);
			}
			
			var done_params = new URLSearchParams($target.attr('data-interaction-done'));

			// Refresh this widget by default
			if(!done_params.has('refresh_widgets[]')) {
				done_params.set('refresh_widgets[]', '{$widget->name}');
			}

			var refresh = done_params.getAll('refresh_widgets[]');

			var widget_ids = [];

			if(-1 !== $.inArray('all', refresh)) {
				// Everything
			} else {
				$popup.find('.cerb-card-widget')
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

				// If nothing to do, abort
				if(0 === widget_ids.length)
					widget_ids = [-1];
			}

			var evt = $.Event('cerb-widgets-refresh', {
				widget_ids: widget_ids,
				refresh_options: { }
			});

			$popup.triggerHandler(evt);
		}
	});
});
</script>