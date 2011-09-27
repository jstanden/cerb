{if !empty($macros)}
<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite sprite-gear"></span> Virtual Attendant</button><!--  
--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
	<li style="background:none;">
		<input type="text" size="32" class="input_search filter">
	</li>
	{foreach from=$macros item=macro key=macro_id}
	<li>
		<a href="javascript:;" onclick="genericAjaxPopup('peek','c=internal&a=showMacroSchedulerPopup&context={$context}&context_id={$context_id}&macro={$macro->id}&return_url={$return_url|escape:'url'}',$(this).closest('ul').get(),true,'400');$(this).closest('ul.cerb-popupmenu').hide();">
			{if !empty($macro->title)}
				{$macro->title}
			{else}
				{$event = DevblocksPlatform::getExtension($macro->event_point, false)}
				{$event->name}
			{/if}
		</a>
	</li>
	{/foreach}
</ul>
{/if}