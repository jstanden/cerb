<form id="frmChartSelector" action="#" style="margin:10px 0px 0px 5px;">
<b>Chart options:</b> 
<button type="button" class="line">Line</button>
<button type="button" class="line stacked">Line (Stacked)</button>
<button type="button" class="bar">Bar</button>
<button type="button" class="bar stacked">Bar (Stacked)</button>
</form>

<script type="text/javascript">
	frm = $('#frmChartSelector').find('button').bind('click', function(event) {
		$choice = $(event.target);
		
		is_bar = $choice.hasClass('bar');
		is_stacked = $choice.hasClass('stacked');
		
		chartOptions.seriesDefaults.renderer = (is_bar) ? $.jqplot.BarRenderer : $.jqplot.LineRenderer;
		if (is_bar) {
			chartOptions.seriesDefaults.rendererOptions.barPadding = 0;
			chartOptions.seriesDefaults.rendererOptions.barMargin = 0;
		}
		chartOptions.stackSeries = (is_stacked) ? true : false;
		
		$('#reportChart').html('');
		var plot1 = $.jqplot('reportChart', chartData, chartOptions);
	});
</script>
