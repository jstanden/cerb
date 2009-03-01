<div id="headerSubMenu">
	<div style="padding-bottom:5px;"></div>
</div>

<script language="javascript" type="text/javascript">
{literal}
function drawChart(field_id) {{/literal}
	YAHOO.widget.Chart.SWFURL = "{devblocks_url}c=resource&p=cerberusweb.core&f=scripts/yui/charts/assets/charts.swf{/devblocks_url}?v={$smarty.const.APP_BUILD}";
	{literal}
	field_id=escape(field_id);
	//[mdf] first let the server tell us how many records to expect so we can make sure the chart height is high enough
	var cObj = YAHOO.util.Connect.asyncRequest('GET', "{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.custom_fields.usage&extid_a=getChart{/devblocks_url}{literal}&field_id="+field_id+"&countonly=1", {
		success: function(o) {
			var rowCount = o.responseText;
			//[mdf] set the chart size based on the number of records we will get from the datasource
			myContainer.style.cssText = 'width:100%;height:'+(30+30*rowCount);;
			
			var myXHRDataSource = new YAHOO.util.DataSource("{/literal}{devblocks_url}ajax.php?c=reports&a=action&extid=report.custom_fields.usage&extid_a=getChart{/devblocks_url}{literal}&field_id="+field_id);
			myXHRDataSource.responseType = YAHOO.util.DataSource.TYPE_TEXT; 
			myXHRDataSource.responseSchema = {
				recordDelim: "\n",
				fieldDelim: "\t",
				fields: [ 
					"value",
					{key:"count", parser:"number"}
				]
			};
	
			var myChart = new YAHOO.widget.BarChart( "myContainer", myXHRDataSource,
			{
				xField: "count",
				yField: "value",
				wmode: "opaque"
				//polling: 1000
			});
			
		},
		failure: function(o) {},
		argument:{caller:this}
		}
	);
}{/literal}
</script>

<h2>{$translate->_('reports.ui.custom_fields.usage')}</h2>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmRange" name="frmRange" onsubmit="return false;" style="margin-bottom:10px;">
<input type="hidden" name="c" value="reports">
<input type="hidden" name="a" value="action">
<input type="hidden" name="extid" value="report.custom_fields.usage">
<input type="hidden" name="extid_a" value="getReport">

<select name="field_id" onchange="this.form.btnSubmit.click();">
	{foreach from=$source_manifests item=mft}
		{foreach from=$custom_fields item=field key=field_id}
			{if 'T' != $field->type && 0==strcasecmp($mft->id,$field->source_extension)}{* Ignore clobs *}
			<option value="{$field_id}">{$mft->name}:{$field->name}</option>
			{/if}
		{/foreach}
	{/foreach}
</select>

<button type="button" id="btnSubmit" onclick="genericAjaxPost('frmRange', 'reportTable');drawChart(this.form.field_id.value);">{$translate->_('common.refresh')|capitalize}</button>
<div id="divCal" style="display:none;position:absolute;z-index:1;"></div>
</form>

<div id="myContainer" style="width:100%;height:0;margin-bottom:10px;background-color:rgb(255,255,255);"></div>

<div id="reportTable" style="background-color:rgb(255,255,255);"></div>
