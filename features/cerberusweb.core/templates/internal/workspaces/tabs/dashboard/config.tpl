<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Dashboard</legend>

<b>Display this many columns:</b><br> 
<select name="params[num_columns]">
	{$num_cols = [1,2,3,4]}
	{foreach from=$num_cols item=num}
	<option value="{$num}" {if $num==$workspace_tab->params.num_columns}selected="selected"{/if}>{$num}</option>
	{/foreach}
</select>
<br>

</fieldset>

<script type="text/javascript">
$fieldset = $('#tabConfig{$workspace_tab->id}');
</script>