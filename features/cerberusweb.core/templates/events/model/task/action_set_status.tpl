<b>{'common.status'|devblocks_translate|capitalize}:</b>
<label><input type="radio" name="{$namePrefix}[status_id]" value="0" {if empty($params) || !$params.status_id}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status_id]" value="2" {if 2 == $params.status_id}checked="checked"{/if}> {'status.waiting'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status_id]" value="1" {if 1 == $params.status_id}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label> 
