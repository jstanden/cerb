{if empty($value_counts)}
	<h3>No data.</h3>
{else}
	{assign var=source value=$field->source_extension}
	<h2>{$source_manifests.$source->name}: {$field->name}</h2>
	<table cellpadding="2" cellspacing="2" border="0">
		<tr>
			<td><b>Value</b></td>
			<td><b>Uses</b></td>
		</tr>
	{foreach from=$value_counts item=count key=value}
		<tr>
			<td>{$value|escape}</td>
			<td align="center">{$count}</td>
		</tr>
	{/foreach}
	</table>
{/if}