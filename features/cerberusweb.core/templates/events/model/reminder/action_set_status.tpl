<b>{'common.status'|devblocks_translate|capitalize}:</b>
<label><input type="radio" name="{$namePrefix}[is_closed]" value="0" {if empty($params) || !$params.is_closed}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[is_closed]" value="1" {if 1 == $params.is_closed}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label> 
