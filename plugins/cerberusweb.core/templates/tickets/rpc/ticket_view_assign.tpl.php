<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="doViewAutoAssign">
<input type="hidden" name="view_id" value="{$view_id}">

{if !empty($num_assignable)}

<H2>Quick Assign</H2>

There are <b>{$num_assignable}</b> assignable tickets in this list.<br>
<br>

<b>Take how many:</b><br>
<input type="text" name="how_many" size="3" value="{$assign_howmany}" maxlength="3"><br>
<br>

<button type="submit" style="">Assign to Me</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');clearDiv('{$view_id}_tips');" style="">Do nothing</button>

{else}

There aren't any assignable tickets in this list.<br>
<br>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');clearDiv('{$view_id}_tips');" style="">Do nothing</button><br>

{/if}

</form>
