{if !empty($macros)}
<button type="button" class="split-left" onclick="$(this).next('button').click();"><span class="cerb-sprite sprite-gear"></span> Virtual Attendant</button><!--  
--><button type="button" class="split-right" id="btnDisplayMacros"><span class="cerb-sprite sprite-arrow-down-white"></span></button>
<ul class="cerb-popupmenu cerb-float" id="menuDisplayMacros">
	<li style="background:none;">
		<input type="text" size="16" class="input_search filter">
	</li>
	{foreach from=$macros item=macro key=macro_id}
	<li><a href="{devblocks_url}c=internal&a=applyMacro{/devblocks_url}?macro={$macro->id}&context={$context}&context_id={$context_id}&return_url={$return_url|escape:'url'}">{$macro->title}</a></li>
	{/foreach}
</ul>
{/if}
