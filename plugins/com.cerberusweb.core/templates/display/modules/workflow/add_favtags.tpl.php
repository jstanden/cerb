<input type="hidden" name="c" value="core.display.module.workflow">
<input type="hidden" name="a" value="saveFavoriteTags">

<br>
<H1>My Favorite Tags</H1>
<b>Add tags separated by commas:</b><br>
<textarea style="width:98%;height:100px;margin:2px;background-color:rgb(255,255,255);border:1px solid rgb(200,200,200);" name="favTagEntry">{foreach from=$favoriteTags item=tag name=tags}{$tag->name}{if !$smarty.foreach.tags.last}, {/if}{/foreach}</textarea>
<br>
<input type="button" value="{$translate->say('common.save_changes')|capitalize}" onclick="displayAjax.saveFavTags();">
<input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="toggleDiv('displayWorkflowOptions','none');">
<br>
