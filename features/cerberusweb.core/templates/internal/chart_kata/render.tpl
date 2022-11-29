{if !$chart_id}{$chart_id = uniqid('chart_')}{/if}
<div id="{$chart_id}"></div>
<div id="{$chart_id}_Legend"></div>

<script type="text/javascript">
$(function() {
    Devblocks.loadResources({
        'css': [
            '/resource/devblocks.core/js/c3/c3.min.css'
        ],
        'js': [
            '/resource/devblocks.core/js/d3/d3.v5.min.js',
            '/resource/devblocks.core/js/c3/c3.min.js',
            '/resource/devblocks.core/js/humanize-duration.js'
        ]
    }, function() {
        try {
            let chart_json = {$chart_json nofilter};
            chart_json.bindto = '#{$chart_id}';
            
            let axes = ['x','y','y2'];
            
            let $legend = $('#{$chart_id}_Legend');
            let legend_style = chart_json.legend['style'] || [];

            var shortEnglishHumanizer = humanizeDuration.humanizer({
                language: 'shortEn',
                spacer: '',
                delimiter: ',',
                languages: {
                    shortEn: {
                        y: () => 'y',
                        mo: () => 'mo',
                        w: () => 'w',
                        d: () => 'd',
                        h: () => 'h',
                        m: () => 'm',
                        s: () => 's',
                        ms: () => 'ms',
                    }
                }
            });

            for(let i = 0; i < axes.length; i++) {
                let axis = axes[i];

                if(chart_json.axis[axis].tick) {
                    if(chart_json.axis[axis].tick.hasOwnProperty('format_options')) {
                        let format_as = chart_json.axis[axis].tick['format_options']['as'];
                        let format_params = chart_json.axis[axis].tick['format_options']['params'];

                        if('date' === format_as) {
                            chart_json.axis[axis].tick.format = d3.timeFormat(format_params['pattern']);

                        } else if('duration' === format_as) {
                            chart_json.axis[axis].tick.format = function(n) {
                                let precision = format_params['precision'];
                                let unit = format_params['unit'];

                                if('seconds' === unit) {
                                    n = parseInt(n) * 1000;
                                } else if('minutes' === unit) {
                                    n = parseInt(n) * 60000;
                                } else if('hours' === unit) {
                                    n = parseInt(n) * 3600000;
                                } else {
                                    n = parseInt(n);
                                }

                                return shortEnglishHumanizer(n, { largest: precision });
                            };

                        } else if('number' === format_as) {
                            chart_json.axis[axis].tick.format = d3.format(format_params['pattern']);
                        }
                    }
                }
            }

            let format_pct = d3.format('.1%');
            let format_num = d3.format(',');

            if(chart_json.tooltip.show && -1 !== $.inArray(chart_json.data.type, ['donut','gauge','pie'])) {
                chart_json.tooltip.format = {
                    value: function (value, ratio) {
                        if(ratio) {
                            return format_num(value) + ' (' + format_pct(ratio) + ')';
                        } else {
                            return format_num(value);
                        }
                    }
                }
            }

            // Click events
            chart_json.data.onclick = function(d, el) {
                if(!chart_json.data.hasOwnProperty('click_search') || 'object' != typeof chart_json.data['click_search'])
                    return;

                if(!chart_json.data['click_search'].hasOwnProperty(d.id + '__click'))
                    return;

                let queries = chart_json.data['click_search'][d.id + '__click'];
                let query = null;

                if(!queries)
                    return;

                if(1 === queries.length) {
                    query = queries[0];
                } else {
                    query = queries[d.index];
                }

                if(!query)
                    return;

                $('<div/>')
                    .attr('data-context', query.substring(0, query.indexOf(' ')))
                    .attr('data-query', query.substring(query.indexOf(' ')+1))
                    .cerbSearchTrigger()
                    .on('cerb-search-opened', function(e) {
                        $(this).remove();
                    })
                    .click()
                ;
            }

            let tooltip_ratios = chart_json.tooltip['ratios'] || false;
            
            // Sort by values
            chart_json.tooltip.order = function (a, b) {
                return b.value - a.value;
            };

            chart_json.tooltip.contents = function(d, defaultTitleFormat, defaultValueFormat, color) {
                // Exclude zero values
                d = d.filter(function(series) {
                    return series.value > 0;
                });
                
                if(0 === d.length)
                    return;
                
                let content = this.getTooltipContent(d, defaultTitleFormat, defaultValueFormat, color);

                // If only one series
                if(1 === d.length)
                    return content;
                
                let $tooltip = $('<div/>').html(content);
                let subtotal = 0;
                let format_pct = d3.format('.1%');

                // Use the raw data so formatters work
                d.map(function(el) {
                    let value = parseFloat(el.value);
                    subtotal += value;
                });

                if(tooltip_ratios && subtotal > 0) {
                    $tooltip.find('th').attr('colspan', 3);
                    
                    $tooltip.find('td.value').map(function (i, el) {
                        let td = document.createElement('td');
                        td.style['text-align'] = 'right';
                        td.innerText = format_pct(parseFloat(d[i].value) / subtotal);
                        el.parentElement.append(td);
                    });
                }
                
                let $tr = $('<tr/>');
                $tr.append($('<td/>').css('font-weight','bold').css('text-align','right').text('Sum'));
                $tr.append($('<td/>').addClass('total').css('font-weight','bold').text(defaultValueFormat(subtotal)));

                if(tooltip_ratios) {
                    $tr.append($('<td/>'));
                }
                
                $tooltip.find('tbody').append($tr);
                
                return $tooltip.html();
            };
            
            let chart = c3.generate(chart_json);
            
            if(legend_style) {
                let legend_style_key = Object.keys(legend_style)[0] || '';
                let data_axes = chart.data.axes();
                let data_names = chart.data.names();
                
                let on_legend_series_click = function(d) {
                    chart.toggle(d.id);
                    let parent;

                    if('table' === legend_style_key) {
                        parent = this.closest('tr');
                    } else {
                        parent = this.closest('div');
                    }
                    
                    if(chart.data.shown(d.id).length > 0) {
                        d3.select(parent).style('opacity', '1.0');
                    } else {
                        d3.select(parent).style('opacity', '0.15');
                    }
                };

                let on_legend_series_mouseover = function(d) {
                    chart.focus(d.id);
                };

                let on_legend_series_mouseout = function(d) {
                    chart.revert();
                };
                
                let doLegendTable = function() {
                    let legend_has_data = legend_style['table'].hasOwnProperty('data') && legend_style['table']['data'];
                    
                    let $table_legend = d3.select('#{$chart_id}_Legend')
                        .style('display', 'block')
                        .style('overflow-x', 'scroll')
                        .style('line-height', '1.5em')
                        .append('table')
                        .attr('align', 'center')
                        .style('text-align', 'center')
                        .style('max-width', '100%')
                        .style('border-spacing', '0')
                        .style('border', '1px solid var(--cerb-color-background-contrast-180)')
                        .style('padding', '0.25em')
                    ;

                    if(legend_has_data) {
                        let $tr_headings = $table_legend.append('thead').append('tr');
                        
                        let y_label = '';
                        
                        if(chart_json.axis.y && chart_json.axis.y.label)
                            y_label = chart_json.axis.y.label;

                        $tr_headings.append('th')
                            .style('width', '150px')
                            .style('overflow', 'hidden')
                            .style('text-overflow', 'ellipsis')
                            .style('padding', '0 0.5em')
                            .style('white-space', 'nowrap')
                            .style('border-bottom', legend_has_data ? '1px solid var(--cerb-color-background-contrast-240)' : '')
                            .text(y_label)
                        ; // Name

                        $tr_headings.append('th')
                            .style('vertical-align','bottom')
                            .style('padding', '0 0.5em')
                            .style('border-right', legend_has_data ? '1px solid var(--cerb-color-background-contrast-240' : '')
                            .style('border-bottom', legend_has_data ? '1px solid var(--cerb-color-background-contrast-240)' : '')
                            .text('')
                        ;

                        let d = chart.data()[0];

                        d.values.forEach(function(v) {
                            let x_value = chart.internal.xAxisTickFormat(v.x);

                            $tr_headings.append('th')
                                .style('overflow', 'hidden')
                                .style('text-overflow', 'ellipsis')
                                .style('white-space', 'nowrap')
                                .style('padding', '0 0.5em')
                                .style('border-bottom', '1px solid var(--cerb-color-background-contrast-240)')
                                .attr('title', x_value)
                                .text(x_value)
                            ;
                        });
                        
                        $tr_headings.selectAll('th').style('background-color', 'var(--cerb-color-background-contrast-230)');
                    }

                    $table_legend
                        .append('tbody')
                        .attr('data-cerb-chart-axis', 'y')
                        .selectAll('tr')
                        .data(chart.data())
                        .enter()
                        .append('tr')
                        .attr('data-cerb-chart-axis', function(d) {
                            return data_axes[d.id] || 'y';
                        })
                        .each(function(series) {
                            var $this = d3.select(this)

                            let $td_name = $this.append('td')
                                .style('overflow', 'hidden')
                                .style('text-overflow', 'ellipsis')
                                .style('white-space', 'nowrap')
                                .style('text-align', 'left')
                                .style('padding', '0 0.5em 0 0.5em')
                            ;

                            // Series swatch
                            $td_name.append('div')
                                .style('display', 'inline-block')
                                .style('vertical-align', 'middle')
                                .style('width', '1em')
                                .style('height', '1em')
                                .style('margin-right', '0.5em')
                                .style('background-color', chart.color(series.id))
                            ;

                            $td_name.append('span')
                                .text(data_names[series.id] || series.id)
                                .style('cursor', 'pointer')
                                .on('click', on_legend_series_click)
                                .on('mouseover', on_legend_series_mouseover)
                                .on('mouseout', on_legend_series_mouseout)
                            ;

                            // Series total

                            let $td_total = $this.append('td')
                                .style('text-align', 'right')
                                .style('padding', '0 0.5em')
                                .style('border-right', legend_has_data ? '1px solid var(--cerb-color-background-contrast-240' : '')
                            ;

                            $td_total.append('span')
                                .style('font-weight','bold')
                                .style('text-align', 'right')
                                .style('vertical-align', 'middle')
                                .style('padding', '0 0.5em')
                                .text(function(d) {
                                    let y = d.values.reduce(function(sum,v) { return sum + v.value; }, 0);

                                    $td_total.attr('data-value', y);

                                    // y1 or y2
                                    let axis = data_axes[d.id] || 'y';

                                    // Use the y-axis formatter
                                    if(chart_json.axis[axis].tick && chart_json.axis[axis].tick.format) {
                                        return chart_json.axis[axis].tick.format(y);
                                    } else {
                                        return y;
                                    }
                                })
                            ;

                            // Series values

                            if(legend_has_data) {
                                series.values.forEach(function(d) {
                                    let axis = data_axes[d.id] || 'y';
                                    let y = d.value;

                                    if(chart_json.axis[axis].tick && chart_json.axis[axis].tick.format) {
                                        y = chart_json.axis[axis].tick.format(y);
                                    }
                                    
                                    let $td = $this.append('td')
                                        .style('text-align', 'right')
                                        .style('padding', '0 1em')
                                        .attr('data-value', d.value)
                                        .text(y)
                                    ;

                                    // Do we have a clickable?
                                    $td
                                        .style('cursor', 'pointer')
                                        .on('click', function() {
                                            chart_json.data.onclick(d);
                                        })
                                    ;
                                });
                            }
                        })
                    ;

                    let $legend_tbody = $legend.find('tbody');

                    // Sort the legend
                    // [TODO] Configurable
                    let $legend_rows = $legend_tbody.find('> tr').toArray().sort(function(a,b) {
                        let a_axis = a.getAttribute('data-cerb-chart-axis');
                        let b_axis = b.getAttribute('data-cerb-chart-axis');

                        if(a_axis !== b_axis) {
                            return a_axis > b_axis ? 1 : -1;
                        }

                        // Sort by label
                        let a_value = a.querySelector('td:nth-child(1)').innerText.toUpperCase();
                        let b_value = b.querySelector('td:nth-child(1)').innerText.toUpperCase();
                        if(a_value === b_value) return 0;
                        return a_value > b_value ? 1 : -1;

                        // Sort by value
                        // let a_value = parseFloat(a.querySelector('td[data-value]').getAttribute('data-value'));
                        // let b_value = parseFloat(b.querySelector('td[data-value]').getAttribute('data-value'));
                        // return b_value - a_value;
                    });
                    $legend_rows.forEach(function(el) {
                        $legend_tbody.append($(el));
                    });

                    // Group y2 rows into a second legend tbody
                    if($legend_tbody.find('tr[data-cerb-chart-axis=y2]').length > 0) {
                        let $legend_tbody_y2 = $('<tbody/>').attr('data-cerb-chart-axis','y2').insertAfter($legend_tbody);
                        let $thead_y2 = $legend_tbody.closest('table').find('thead').clone(false).insertAfter($legend_tbody);

                        $legend_tbody.find('tr[data-cerb-chart-axis=y2]').appendTo($legend_tbody_y2);

                        let y2_label = 'y2';
                        
                        if(chart_json.axis.y2 && chart_json.axis.y2.label)
                            y2_label = chart_json.axis.y2.label;
                        
                        $thead_y2.find('tr:first th:first').text(y2_label);
                        
                        // If we have nothing left in the y-axis, remove that legend
                        if(0 === $legend_tbody.find('tr').length) {
                            $legend_tbody.remove();
                        } else {
                            $thead_y2.find('tr:first th')
                                .css('background-color', 'var(--cerb-color-background-contrast-230)')
                            ;
                        }
                    }

                    // Stats on each column
                    
                    let stats = chart_json.legend.style.table.stats || [];
                    
                    if(stats.length > 0) {
                        let format_avg = d3.format('.2f');
                        
                        stats.reverse().forEach(function(stat,i) {
                            $legend.find('table tbody[data-cerb-chart-axis]').each(function() {
                                let $tbody = $(this);
                                let $tbody_stats = $('<tbody/>').insertAfter($tbody);
                                
                                if($tbody.find('tr').length < 2)
                                    return;
    
                                let axis = $tbody.attr('data-cerb-chart-axis');
    
                                let $totals_tr = $('<tr/>')
                                    .appendTo($tbody_stats)
                                ;
    
                                $tbody.find('tr:first td').each(function() {
                                    let $td = $(this);
                                    let index = $td.index();
                                    let computed;
                                    
                                    if($td.attr('data-value')) {
                                        let values = $tbody.find('tr').find('td:nth(' + index + ')').toArray();
                                        
                                        switch(stat) {
                                            case 'avg':
                                            case 'sum':
                                                computed = values.reduce(function(sum,v) {
                                                    return sum + parseFloat(v.getAttribute('data-value'));
                                                }, 0);
                                                
                                                if('avg' === stat)
                                                    computed = format_avg(computed / values.length);

                                                if(chart_json.axis[axis].tick && chart_json.axis[axis].tick.format) {
                                                    computed = chart_json.axis[axis].tick.format(computed);
                                                }
                                                break;
                                            
                                            case 'min':
                                                computed = Math.min(...values.map(function(v) {
                                                    return v.getAttribute('data-value');
                                                }));
                                                
                                                if(chart_json.axis[axis].tick && chart_json.axis[axis].tick.format) {
                                                    computed = chart_json.axis[axis].tick.format(computed);
                                                }
                                                break;
                                                
                                            case 'max':
                                                computed = Math.max(...values.map(function(v) {
                                                    return v.getAttribute('data-value');
                                                }));
                                                
                                                if(chart_json.axis[axis].tick && chart_json.axis[axis].tick.format) {
                                                    computed = chart_json.axis[axis].tick.format(computed);
                                                }
                                                break;
                                                
                                            case 'count':
                                                computed = values.map(function(v) {
                                                    return v.getAttribute('data-value');
                                                }).length;
                                                break;
                                        }
    
                                    } else {
                                        if(0 === index) {
                                            computed = stat;
                                        } else {
                                            computed = '';
                                        }
                                    }
    
                                    $totals_tr.append(
                                        $('<td/>')
                                            .css('font-weight', 'bold')
                                            .css('text-align', 'right')
                                            .css('padding', '0 1em')
                                            .css('border-top', (stats.length -1 === i ? '5px' : '1px') + ' solid var(--cerb-color-background-contrast-240)')
                                            .css('border-right', legend_has_data && 1 === index ? '1px solid var(--cerb-color-background-contrast-240' : '')
                                            .text(computed)
                                    );
                                });
                            });                        
                        });
                    }
                };
                
                let doLegendCompact = function() {
                    let $table_legend = d3.select('#{$chart_id}_Legend')
                        .style('line-height', '1.5em')
                        .style('text-align', 'center')
                        .append('div')
                        .style('max-width', '90%')
                        .style('display', 'inline-block')
                        .style('border', '1px solid var(--cerb-color-background-contrast-240)')
                        .style('padding', '0.25em')
                    ;
                    
                    $table_legend
                        .selectAll('div')
                        .data(chart.data())
                        .enter()
                        .append('div')
                        .style('display', 'inline-block')
                        .attr('data-cerb-chart-axis', function(d) {
                            return data_axes[d.id] || 'y';
                        })
                        .each(function(series) {
                            var $this = d3.select(this)

                            let $span_name = $this.append('span')
                                .style('text-align', 'left')
                                .style('padding', '0 0.5em 0 0.5em')
                                .style('cursor', 'pointer')
                                .on('click', on_legend_series_click)
                                .on('mouseover', on_legend_series_mouseover)
                                .on('mouseout', on_legend_series_mouseout)
                            ;

                            // Series swatch
                            $span_name.append('div')
                                .style('display', 'inline-block')
                                .style('vertical-align', 'middle')
                                .style('width', '1em')
                                .style('height', '1em')
                                .style('margin-right', '0.5em')
                                .style('background-color', chart.color(series.id))
                            ;

                            $span_name.append('span').text(data_names[series.id] || series.id);
                        })
                    ;

                    // Sort the legend
                    
                    let $compact_legend = $('#{$chart_id}_Legend').find('> div');
                    
                    let $legend_rows = $compact_legend.find('> div').toArray().sort(function(a,b) {
                        let a_axis = a.getAttribute('data-cerb-chart-axis');
                        let b_axis = b.getAttribute('data-cerb-chart-axis');

                        if(a_axis !== b_axis) {
                            return a_axis > b_axis ? 1 : -1;
                        }

                        // Sort by label
                        let a_value = a.innerText.toUpperCase();
                        let b_value = b.innerText.toUpperCase();
                        if(a_value === b_value) return 0;
                        return a_value > b_value ? 1 : -1;
                    });
                    $legend_rows.forEach(function(el) {
                        $compact_legend.append($(el));
                    });
                    
                    // Group y2 rows into a second legend container
                    if($compact_legend.find('div[data-cerb-chart-axis=y2]').length > 0) {
                        let $compact_legend_y2 = $($compact_legend.get(0).cloneNode())
                            .attr('data-cerb-chart-axis','y2')
                            .insertAfter($compact_legend)
                        ;
                        $('<br/>').insertAfter($compact_legend);

                        $compact_legend.find('div[data-cerb-chart-axis=y2]').appendTo($compact_legend_y2);

                        // If we have nothing left in the y-axis, remove that legend
                        if(0 === $compact_legend.find('div').length) {
                            $compact_legend.remove();
                        } else {
                            $compact_legend.css('margin-bottom', '0.25em')
                        }
                    }
                }
                
                if('compact' === legend_style_key) {
                    doLegendCompact();
                } else if('table' === legend_style_key) {
                    doLegendTable();
                }
            }

        } catch(e) {
            $('#{$chart_id}').text(e.message);

            if(console && 'function' == typeof console.error)
                console.error(e);
        }
    });
});
</script>