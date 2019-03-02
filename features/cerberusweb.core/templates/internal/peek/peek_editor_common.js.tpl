{* Run custom jQuery scripts from bot behaviors *}
{$bot_actions = []}
{$bot_behaviors = []}
{Event_UiCardEditorOpenedByWorker::triggerForWorker($active_worker, $peek_context, $peek_context_id, $bot_actions, $bot_behaviors)}

{if !empty($bot_behaviors)}
	{if $bot_actions.jquery_scripts}
	{
		{foreach from=$bot_actions.jquery_scripts item=jquery_script}
		try {
			{$jquery_script nofilter}
		} catch(e) { }
		{/foreach}
		
		// Render editor behavior list
		
		var $bot_marquee = $('<div/>')
			.addClass('block')
			.css('margin-top', '10px')
			.appendTo($popup)
			;
			
		var $bot_marquee_heading = $('<b/>')
			.css('margin-right', '5px')
			.text('This editor was modified by bots:')
			.prependTo($bot_marquee)
			;
		
		var $bot_marquee_ul = $('<ul/>')
			.addClass('bubbles')
			.appendTo($bot_marquee)
			;
		
		{foreach from=$bot_behaviors item=bot_behavior}
		var $bot_marquee_item = $('<li/>')
			.appendTo($bot_marquee_ul)
			;
		
		var $bot_marquee_avatar = $('<img/>')
			.attr('src', '{devblocks_url}c=avatars&context=bot&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}')
			.addClass('cerb-avatar')
			.prependTo($bot_marquee_item)
			;
			
		var $bot_marquee_label = $('<a/>')
			.text("{$bot_behavior->title|escape:'js'}")
			.attr('href', 'javascript:')
			.attr('data-context','behavior')
			.attr('data-context-id','{$bot_behavior->id}')
			.cerbPeekTrigger()
			.appendTo($bot_marquee_item)
			;
		{/foreach}
	}
	{/if}
{/if}