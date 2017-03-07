{if !empty($macros)}
<button type="button" class="split-left" onclick="$(this).next('button').click();" title="{'common.bots'|devblocks_translate|capitalize}{if $pref_keyboard_shortcuts} (M){/if}"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:22px;height:22px;margin:-3px 0px 0px 2px;"></button><!--  
--><button type="button" class="split-right" id="btnDisplayMacros"><span class="glyphicons glyphicons-chevron-down" style="font-size:12px;color:white;"></span></button>
<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
	<li style="background:none;">
		<input type="text" size="32" class="input_search filter">
	</li>
	
	{$bots = DAO_Bot::getAll()}
	
	{foreach from=$bots item=bot}
		{capture name=behaviors}
		{foreach from=$macros item=behavior key=behavior_id}
		{if $behavior->bot_id == $bot->id && !$behavior->is_private}
			<li class="item item-behavior">
				<div style="margin-left:20px;">
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showMacroSchedulerPopup&context={$context}&context_id={$context_id}&macro={$behavior->id}&return_url={$return_url|escape:'url'}',$(this).closest('ul').get(),false,'50%');$(this).closest('ul.cerb-popupmenu').hide();">
						{if !empty($behavior->title)}
							{$behavior->title}
						{else}
							{$event = DevblocksPlatform::getExtension($behavior->event_point, false)}
							{$event->name}
						{/if}
					</a>
				</div>
			</li>
		{/if}
		{/foreach}
		{/capture}
		
		{if strlen(trim($smarty.capture.behaviors))}
		<li class="item-va">
			<div>
				<img src="{devblocks_url}c=avatars&context=bot&id={$bot->id}{/devblocks_url}?v={$bot->updated_at}" style="width:16px;height:16px;border-radius:8px;vertical-align:middle;"> <a href="javascript:;" style="color:black;" tabindex="-1">{$bot->name}</a>
			</div>
		</li>
		{$smarty.capture.behaviors nofilter}
		{/if}
	{/foreach}
</ul>
{/if}