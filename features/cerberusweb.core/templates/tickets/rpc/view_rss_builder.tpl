<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewBuildRss">
<input type="hidden" name="view_id" value="{$view_id}">

<H3>Create RSS Feed</H3>
<br>

<b>Feed Title:</b><br>
<input type="text" name="title" value="{$view->name}" size="45">
<br>

<br>
<button type="button" onclick="this.form.submit();" style=""><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');$('#{$view_id}_tips').html('');" style=""><span class="cerb-sprite2 sprite-cross-circle"></span> Cancel</button>

</form>