{foreach from=$addresses item=address}
<label><input type="checkbox" name="do_email[]" value="{$address->address}"> {$address->address}</label><br>
{/foreach}
