<b>{'common.status'|devblocks_translate|capitalize}:</b>

<label><input type="radio" name="{$namePrefix}[status]" value="open" {if empty($params) || $params.status=='open'}checked="checked"{/if}> {'crm.opp.status.open'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status]" value="closed_won" {if $params.status=='closed_won'}checked="checked"{/if}> {'crm.opp.status.closed.won'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[status]" value="closed_lost" {if $params.status=='closed_lost'}checked="checked"{/if}> {'crm.opp.status.closed.lost'|devblocks_translate|capitalize}</label> 
