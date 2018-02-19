<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Dashboard</legend>

{$params_num_tabs = $workspace_tab->params.num_columns|default:3}

<b>Display this many columns:</b><br> 
<select name="params[num_columns]">
	{$num_cols = [1,2,3,4]}
	{foreach from=$num_cols item=num}
	<option value="{$num}" {if $num==$params_num_tabs}selected="selected"{/if}>{$num}</option>
	{/foreach}
</select>
<br>

</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#tabConfig{$workspace_tab->id}');
});
</script>