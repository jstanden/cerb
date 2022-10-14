<div id="widget{$widget->id}"></div>

<script type="text/javascript">
    $(function() {
        function CerbCalendarHoursByDay(data, {
            x = function([x]) { return x; },
            y = function([,y]) { return y; },
            width = 675,
            cellSize = 22
        } = { }) {
            var X = d3v7.map(data, x);
            var Y = d3v7.map(data, y);
            var I = d3v7.range(X.length);

            var x_extents = d3v7.extent(X);

            var firstDay = new Date(x_extents[0]);
            firstDay.setHours(0,0,0,0);

            var formatYearMonthDay = d3v7.timeFormat("%Y-%m-%d");
            var formatYAxis = d3v7.timeFormat("%a, %b %d");
            var formatYAxisWithYear = d3v7.timeFormat("%Y %a, %b %d");
            var formatTitle = d3v7.timeFormat("%a, %b %d %Y %H:00");

            var timeHour = function(i) {
                return i.getHours();
            }

            var daySequence = function(i) {
                var d = new Date(i);
                d.setHours(0,0,0,0);
                return Math.floor((d.getTime() - firstDay.getTime())/86400000);
            }

            var fill_extents = d3v7.extent(Y);

            // If we have no data, we need a scale where 0 isn't filled
            if('0,0' === fill_extents.join())
                fill_extents = [0,60];

            var fill_color_from = '#ffffff';
            var fill_color_to = 'rgb(19,134,3)';
            var fill_classes = 12;

            if('function' == typeof getComputedStyle) {
                fill_color_from = getComputedStyle(document.documentElement).getPropertyValue('--cerb-color-background');
            }

            var colorFill = d3v7.scaleQuantize()
                .domain(fill_extents)
                .range(d3v7.quantize(d3v7.interpolateRgb(fill_color_from,fill_color_to), fill_classes))
            ;

            var title = function(i) {
                return formatTitle(X[i]) + "\n" + Y[i] + " minutes available";
            }

            var days = d3v7.groups(I, function(i) { return formatYearMonthDay(X[i]); });
            var hours = d3v7.groups(I, function(i) { return X[i].getHours(); });

            // Sort the hours
            hours.sort(function(a,b) { return a[0]-b[0]; });

            var height = 60 + days.length * cellSize;

            var svg = d3v7.create("svg")
                .attr("width", width)
                .attr("height", height)
                .attr("viewBox", [0, 0, width, height])
                .attr("style", "max-width: 100%; height: auto; height: intrinsic;")
                .attr("font-family", "sans-serif")
                .attr("font-size", 16)
                .style("fill", "var(--cerb-color-text)")
                .on("click", function(e) {
                    e.stopPropagation();
                    // [TODO] Launch interactions
                    //console.log(e);
                })
            ;

            var day = svg.append("g")
                .selectAll("text")
                .data(days)
                .join("text")
                .attr('font-size', 14)
                .attr("text-weight", "normal")
                .attr("text-anchor", "end")
                .attr("transform", function(d,i) {
                    var y = 60 + (i * cellSize);
                    return "translate(0," + y + ")";
                })
                .text(function(d,i) {
                    // [TODO] Bold the current time tick
                    // If the first row or a new year, prefix the year
                    if(0 === i || '-01-01' === d[0].slice(-6))
                        return formatYAxisWithYear(new Date(d[0] + "T00:00:00"));

                    return formatYAxis(new Date(d[0] + "T00:00:00"));
                })
            ;

            var hour = svg.selectAll("g")
                .data(hours)
                .join("g")
                .attr("transform", "translate(120,0)")
            ;

            hour.append("text")
                .attr("font-weight", "normal")
                .attr("text-anchor", "end")
                .attr("transform", function(d,i) {
                    var x = 1.25 * cellSize + (i * cellSize);
                    return "translate(" + x + ",0) rotate(-90)";
                })
                .text(function(d) {
                    return 0 === d[0] % 2 ? (('00' + d[0]).slice(-2) + ':00') : '';
                })
            ;

            var cell = hour.append("g")
                .attr("transform", "translate(11,45)")
                .selectAll("rect")
                .data(function([,I]) { return I; })
                .join("rect")
                .attr("width", cellSize)
                .attr("height", cellSize)
                .attr("x", function(i) { return timeHour(X[i]) * cellSize; })
                .attr("y", function(i) { return daySequence(X[i]) * cellSize; })
                .attr("fill", function(i) { return colorFill(Y[i]); })
                .attr("stroke", function(i) {
                    return d3v7.rgb(colorFill(Y[i])).darker(0.5);
                })
                .attr("stroke-width", 0.5)
            ;

            if(title) {
                cell.append("title").text(title);
            }

            return Object.assign(svg.node(), { scales: { colorFill } });
        }

        Devblocks.loadResources({
            'js': [
                '/resource/devblocks.core/js/d3/d3.v7.min.js?v={$smarty.const.APP_BUILD}'
            ]
        }, function () {
            try {
                var $widget = $('#widget{$widget->id}');

                var data = {$data nofilter};

                var svg = CerbCalendarHoursByDay(data, {
                    x: function(d) { return new Date(d.date); },
                    y: function(d) { return d.value; },
                });

                $widget.empty().append(svg);

            } catch(e) {
                console.error(e);
            }
        });
    });
</script>