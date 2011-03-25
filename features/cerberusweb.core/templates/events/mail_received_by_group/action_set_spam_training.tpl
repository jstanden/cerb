<b>{'ticket.spam_training'|devblocks_translate|capitalize}:</b>

<label><input type="radio" name="{$namePrefix}[value]" value="N" {if empty($params) || $params.value=='N'}checked="checked"{/if}> {'common.notspam'|devblocks_translate|capitalize}</label> 
<label><input type="radio" name="{$namePrefix}[value]" value="S" {if $params.value=='S'}checked="checked"{/if}> {'common.spam'|devblocks_translate|capitalize}</label> 