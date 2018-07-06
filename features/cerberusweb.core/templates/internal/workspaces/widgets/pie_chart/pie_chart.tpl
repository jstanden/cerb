<div id="widget{$widget->id}"></div>
<div id="widget{$widget->id}Legend"></div>

<script type="text/javascript">
$(function() {
	Devblocks.loadResources({
		'css': [
			'/resource/devblocks.core/js/c3/c3.min.css'
		],
		'js': [
			'/resource/devblocks.core/js/d3/d3.v5.min.js',
			'/resource/devblocks.core/js/c3/c3.min.js'
		]
	}, function() {
		try {
			var $widget = $('#widget{$widget->id}');

			var json = {$data_json nofilter};
				
			var chart = null;

			chart = c3.generate({
				bindto: '#widget{$widget->id}',
				data: {
					columns: json,
					type: 'donut'
				},
				size: {
					height: 250
				},
				donut: {
					label: {
						format: function(d) {
							return '';
						}
					}
				},
				legend: {
					show: false
				}
			});
			
			d3.select('#widget{$widget->id}Legend').selectAll('div')
				.data(json.map(function(d) { return d[0]; }))
				.enter()
					.append('div')
					.attr('data-id', function (id) { return id; })
					.each(function(id, i) {
						var $this = d3.select(this)
							.style('display', 'inline-block')
							.style('cursor', 'pointer')
							.on('mouseover', function (id) {
								chart.focus(id);
							})
							.on('mouseout', function (id) {
								chart.revert();
							})
							.on('click', function (id) {
								chart.toggle(id);
							})
							;
						
						var $badge = $this.append('div')
							.style('display', 'inline-block')
							.style('vertical-align', 'middle')
							.style('width', '1em')
							.style('height', '1em')
							.style('background-color', chart.color(id))
							;
						
						var $text = $this.append('span')
							.style('vertical-align', 'middle')
							.style('margin', '0px 0px 0px 5px')
							.style('font-weight', 'bold')
							.text(id)
							;
						
						var $text = $this.append('span')
							.style('vertical-align', 'middle')
							.style('margin', '0px 1em 0px 2px')
							.text('(' + json[i][1] + ')')
							;
					})
				;

		} catch(e) {
			console.error(e);
		}
	});
});
</script>