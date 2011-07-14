<b>{'common.status'|devblocks_translate|capitalize}:</b>

<label><input type="radio" name="{$namePrefix}[status]" value="active" {if empty($params) || $params.status=='active'}checked="checked"{/if}> {'task.status.active'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status]" value="completed" {if $params.status=='completed'}checked="checked"{/if}> {'task.status.completed'|devblocks_translate|capitalize}</label> 
