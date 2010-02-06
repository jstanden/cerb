<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<h2>{$translate->_('reports.ui.custom_fields.usage')}</h2>

<form action="{devblocks_url}c=reports&report=report.custom_fields.usage{/devblocks_url}" method="POST" id="frmRange" name="frmRange" style="margin-bottom:10px;">
<input type="hidden" name="c" value="reports">

<select name="field_id" onchange="this.form.btnSubmit.click();">
	{foreach from=$source_manifests item=mft}
		{foreach from=$custom_fields item=f key=field_idx}
			{if 'T' != $f->type && 0==strcasecmp($mft->id,$f->source_extension)}{* Ignore clobs *}
			<option value="{$field_idx}" {if $field_id==$field_idx}selected="selected"{/if}>{$mft->name}:{$f->name}</option>
			{/if}
		{/foreach}
	{/foreach}
</select>

<button type="submit" id="btnSubmit">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
</form>

<!-- Chart -->

{if !empty($data)}
<div id="placeholder" style="margin:1em;width:650px;height:{20+(32*count($data))}px;"></div>

<script language="javascript" type="text/javascript">
	$(function() {
		var d = [
			{foreach from=$data item=row key=iter}
			[{$row.hits}, {$iter}],
			{/foreach}
		];
		
		var options = {
			lines: { show: false, fill: false },
			bars: { show: true, fill: true, horizontal: true, align: "center", barWidth: 1 },
			points: { show: false, fill: false },
			grid: {
				borderWidth: 0,
				horizontalLines: false,
				hoverable: false,
			},
			xaxis: {
				min: 0,
				minTickSize: 1,
				tickFormatter: function(val, axis) {
					return Math.floor(val).toString();
				},
			},
			yaxis: {
				ticks: [
					{foreach from=$data item=row key=iter}
					[{$iter},"<b>{$row.value|escape:'quotes'}</b>"],
					{/foreach}
				]
			}
		} ;
		
		$.plot($("#placeholder"), [d], options);
	} );
</script>
{/if}

<!-- Table -->

{if empty($value_counts)}
	<h3>No data.</h3>
{else}
	{$manifest = $source_manifests.{$f->source_extension}}
	<h2>{$manifest->name}: {$field->name}</h2>
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
