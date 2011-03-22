<label><input type="radio" name="{$namePrefix}[bool]" value="1" {if !isset($params.bool) || !empty($params.bool)}checked="checked"{/if}> True</label>
<label><input type="radio" name="{$namePrefix}[bool]" value="0" {if isset($params.bool) && empty($params.bool)}checked="checked"{/if}> False</label>
