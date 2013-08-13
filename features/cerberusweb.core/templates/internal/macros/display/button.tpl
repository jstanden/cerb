{if !empty($macros)}
<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite2 sprite-robot"></span> Virtual Attendants</button><!--  
--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
	<li style="background:none;">
		<input type="text" size="32" class="input_search filter">
	</li>
	
	{$vas = DAO_VirtualAttendant::getWhere()}
	
	{foreach from=$vas item=va}
		{capture name=behaviors}
		{foreach from=$macros item=behavior key=behavior_id}
		{if $behavior->virtual_attendant_id == $va->id}
			<li class="item item-behavior">
				<div style="margin-left:10px;">
					<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showMacroSchedulerPopup&context={$context}&context_id={$context_id}&macro={$behavior->id}&return_url={$return_url|escape:'url'}',$(this).closest('ul').get(),false,'600');$(this).closest('ul.cerb-popupmenu').hide();">
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
				<a href="javascript:;" style="color:black;" tabindex="-1">{$va->name}</a>
			</div>
		</li>
		{$smarty.capture.behaviors nofilter}
		{/if}
	{/foreach}
</ul>
{/if}