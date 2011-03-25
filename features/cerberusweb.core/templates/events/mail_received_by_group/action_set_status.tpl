<b>{'common.status'|devblocks_translate|capitalize}:</b>

<label><input type="radio" name="{$namePrefix}[status]" value="open" {if empty($params) || $params.status=='open'}checked="checked"{/if}> {'status.open'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status]" value="waiting" {if $params.status=='waiting'}checked="checked"{/if}> {'status.waiting'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status]" value="closed" {if $params.status=='closed'}checked="checked"{/if}> {'status.closed'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status]" value="deleted" {if $params.status=='deleted'}checked="checked"{/if}> {'status.deleted'|devblocks_translate|capitalize}</label> 
