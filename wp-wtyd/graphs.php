<div id='wtyd_graph'>
	<div id='graph_nav'>
		<span>Line Graph</span> | <span><a href="javascript:;">Scatter Plot</a></span>
			<strong class='buffer'>Between</strong>
			<select class='start'></select>
			<strong>and</strong>
			<select class='end'></select>
	</div>
	<div id='graph_lines'>
		<div class='blog_data small'>
			<strong>Left Y Axis:</strong>
		</div>
		<div class='blog_stats'></div>
		<div class='blog_data large'>
			<strong>Right Y Axis:</strong>
			<div class='legend'></div>
		</div>
		</div>
	<div id='graph_plot'>
		<div class='blog_data small'>
			<strong>Y-Axis:</strong>
			<select class='y'></select>
			<strong>X-Axis:</strong>
			<select class='x'></select>
		</div>
		<div class='blog_stats'></div>
		<div class='blog_data large'>
			<strong>Correlation:</strong>
			<span class="correlation"></span>
			<strong>Description:</strong>
			<span class="description"></span>
			<div class='legend'></div>
		</div>
	</div>
</div>
<script>
(function($){
	$(function () {
	
		/** correlation */
		
		function correlation(data) {
			n = data.length;
			var sumx = 0;
			var sumy = 0;
			var sumsqx = 0;
			var sumsqy = 0;
			var sumxy = 0;
			
			for (var i=0; i<n; i++) {
				sumx = sumx + (data[i][0] * 1)
			}
			
			for (var i=0; i<n; i++) {
				sumy = sumy + (data[i][1] * 1)
			}
			
			for (var i=0; i<n; i++) {
				sumxy = sumxy + (data[i][0] * data[i][1]);
			}
			
			for (var i=0; i<n; i++) {
				sumsqx = sumsqx + (data[i][0] * data[i][0])
			}
			
			for (var i=0; i<n; i++) {
				sumsqy = sumsqy + (data[i][1] * data[i][1])
			}
			
			var numerator	= n*sumxy - sumx*sumy;
			var denominator	= Math.sqrt(n*sumsqx - sumx*sumx) * Math.sqrt(n*sumsqy - sumy*sumy);
			r = numerator / denominator;
			
			var ssx = sumsqx-((sumx*sumx)/n);
			var ssy = sumsqy-((sumy*sumy)/n);
			var ssxy = sumxy-((sumx*sumy)/n);
			
//									document.show0.elements[0].value = Math.round(sumx*10000)/10000;
//									document.show0.elements[1].value = Math.round(sumsqx*10000)/10000;
//									document.show0.elements[2].value = Math.round(sumy*10000)/10000;
//									document.show0.elements[3].value = Math.round(sumsqy*10000)/10000;
//									document.show0.elements[4].value = Math.round(sumxy*10000)/10000;
			
//									r = ssxy/Math.sqrt(ssx*ssy);
			
			t_denom =Math.sqrt( (1-(r*r))/(n-2));
			var t = r/t_denom;
//									document.t.elements[0].value =  Math.round(t*1000)/1000;
			
			var rsq = r*r;
			var slo = ssxy/ssx;
			var intercept = (sumy / n)-(slo*(sumx/n));
			var se = Math.sqrt((ssy*(1-rsq))/(n-2));
			
			return r;
		}

		

	
		/** graph data **/
		
		
		function attachGraphEvents(){
			$("#graph_nav span:first a").click(function(){
				$("#graph_nav span:last").html("<a href='javascript:;'>" + $("#graph_nav span:last").text() + "</a>");
				$("#graph_nav span:first").html($("#graph_nav span:first").text());
				attachGraphEvents();
				$("#graph_lines").show();
				$("#graph_plot").hide();
				rebuildLineGraph();
			});
			$("#graph_nav span:last a").click(function(){
				$("#graph_nav span:first").html("<a href='javascript:;'>" + $("#graph_nav span:first").text() + "</a>");
				$("#graph_nav span:last").html($("#graph_nav span:last").text());
				attachGraphEvents();
				$("#graph_plot").show();
				$("#graph_lines").hide();
				rebuildScatterGraph();
			});
		}
		$(attachGraphEvents);
	
	



		var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

		var all_data = <?= $this->echoScript()?>
	    
		for(var i=0;i<all_data.length;i++){
			if(all_data[i].max2){
				$("#graph_lines .blog_data.large").append(all_data[i].dom);
			}else{
				$("#graph_lines .blog_data.small").append(all_data[i].dom);
			}
			$("#graph_plot .blog_data.small select:not(.start):not(.end)").append("<option value='" + i + "'>" + all_data[i].label + "</option>");
		}
		
		
		var mintime = WTYD_MINTIME;
		var now = new Date();
		do{
			var dt = new Date();
			dt.setTime(mintime);
			dt.setUTCDate(1);
			dt.setUTCHours(0);
			dt.setUTCMinutes(0);
			dt.setUTCSeconds(0);
			dt.setUTCMilliseconds(0);

			var max = new Date();
			max.setTime(mintime);
			max.setUTCMonth(dt.getUTCMonth() + 1);
			max.setUTCDate(0);
			max.setUTCHours(0);
			max.setUTCMinutes(0);
			max.setUTCSeconds(0);
			max.setUTCMilliseconds(0);
			$("#graph_nav select.start").append("<option value='" + dt.getTime() + "'>" + monthNames[dt.getUTCMonth()] + " " + dt.getUTCFullYear() + "</option>");
			$("#graph_nav select.end").append("<option value='" + max.getTime() + "'>" + monthNames[max.getUTCMonth()] + " " + max.getUTCFullYear() + "</option>");
			
			dt.setUTCMonth(dt.getUTCMonth()+1);
			mintime = dt.getTime();
		}while(mintime < now.getTime());
		
		$("#graph_nav select.start option:last").remove();
		$("#graph_nav select.end option:first").remove();
		$("#graph_nav select.end option:last").attr("SELECTED", "1");

	
		var lineOptions = { lines: { show: true },
	             points: { show: false },
	             selection: { mode: "x" },
	             grid: { hoverable: true },
	             xaxis: {
	             	mode: "time",
		         	minTickSize: [1, "month"],
	           		tickSize: [1, "month"],
		            timeformat: "%b",
				 },
				 yaxis: { min: 0 },
				 y2axis: { min: 0 },
	             monthNames: monthNames,
	             legend: {
				    show: true,
				    noColumns: 2,
				    margin: 10,
				    container: $("#graph_lines .blog_data .legend")
				  }
	             };
	
		var plotOptions = { lines: { show: true },
	             points: { show: true },
	             lines: { show : false },
	             selection: { mode: "x" },
	             grid: { hoverable: true },
	             xaxis: { min: 0 },
				 yaxis: { min: 0 },
	             timeformat: "%b %d %y",
	             monthNames: monthNames,
	             legend: {
				    show: true,
				    noColumns: 2,
				    margin: 10,
				    container: $(".blog_data #legend")
				  }
	             };
	
		function rebuildLineGraph(){
			var data = new Array();
			var mindate = $("#graph_nav select.start option:selected").val();
			var maxdate = $("#graph_nav select.end option:selected").val();
			var max = 0;
			var max2 = 0;
			for(var i=0;i<all_data.length;i++){
				if(all_data[i].dom.find("input:checked").length){
					var newData = new Array();
					for(var j=0;j<all_data[i].data.length;j++){
						var d = all_data[i].data[j][0];
						if(d >= mindate && d <= maxdate){
							newData.push(all_data[i].data[j]);
						}
					}
					if(newData.length){
						var newObj = { };
						for(var theVar in all_data[i]){
							if(theVar == "data"){
								newObj[theVar] = newData;
							}else{
								newObj[theVar] = all_data[i][theVar];
							}
						}
						data.push(newObj);
						if(all_data[i].max > max) max = all_data[i].max;
						if(all_data[i].max2 > max2) max2 = all_data[i].max2;
						lineOptions.yaxis.max = max + Math.ceil(max * 0.2);
						lineOptions.y2axis.max = max2 + Math.ceil(max2 * 0.2);
					}
				}
			}
			var plot = $.plot($("#graph_lines .blog_stats"),
						    data,
						    lineOptions);
						    
						    
		    var series = plot.getData();
		}
		
		
		function rebuildScatterGraph(){
			var data = new Array();
			var combined = new Array();
			var xmax = 0;
			var ymax = 0;
			var xmin = 100000000;
			var ymin = 100000000;
			
			var id1 = $("#graph_plot .blog_data.small select.x option:selected").val();
			var id2 = $("#graph_plot .blog_data.small select.y option:selected").val();
			var mindate = $("#graph_nav select.start option:selected").val();
			var maxdate = $("#graph_nav select.end option:selected").val();
			
			
			// make sure that both data sets start on the same date
			var dset1 = all_data[id1].data.slice(0);
			var dset2 = all_data[id2].data.slice(0);
			
			
			while(dset1.length && dset2.length && dset1[0][0] < dset2[0][0]){
				dset1 = dset1.slice(1)
			}
			while(dset1.length && dset2.length && dset2[0][0] < dset1[0][0]){
				dset2 = dset2.slice(1)
			}

			
            for (var v in dset1){
            	var x = dset1[v];
				var xval = x ? x[1] : 0;
            	var y = dset2[v];
				var yval = y ? y[1] : 0;
				var d = (x ? x[0] : (y ? y[0] : 0));
				
				var dt = new Date();
				dt.setTime(d);
				var mindatet = new Date();
				mindatet.setTime(mindate);
//										alert( dt.toUTCString() + "\n >= \n" + mindatet.toUTCString());
				
				if(xval && yval && d >= mindate && d <= maxdate){
                	if(xmax < xval) xmax = xval;
                	if(ymax < yval) ymax = yval;
                	if(xmin > xval) xmin = xval;
                	if(ymin > yval) ymin = yval;
                	combined.push([ xval, yval ]);
				}
            }
			data.push({color: 6, data: combined});

			plotOptions.xaxis.label = all_data[id1].label;
			plotOptions.yaxis.label = all_data[id2].label;
			plotOptions.xaxis.max = xmax + Math.ceil(xmax * 0.2);
			plotOptions.yaxis.max = ymax + Math.ceil(ymax * 0.2);
			plotOptions.xaxis.min = xmin - Math.ceil(xmin * 0.2);
			plotOptions.yaxis.min = ymin - Math.ceil(ymin * 0.2);
			var plot = $.plot($("#graph_plot .blog_stats"),
						    data,
						    plotOptions);
						    
						    
			var r = correlation(combined);									
			$("#graph_plot .blog_data.large .correlation").text(r.toFixed(4));
			
			var desc = "";
			if(r > .5){
				desc = "More " + all_data[id1].label + " means more " + all_data[id2].label;
			}else if(r > .3){
				desc = "More " + all_data[id1].label + " means kinda more " + all_data[id2].label;
			}else if(r > -0.3){
				desc = "Not much to say about " + all_data[id1].label + " and " + all_data[id2].label;
			}else if(r > -0.5){
				desc = "More " + all_data[id1].label + " means kinda less " + all_data[id2].label;
			}else if(r > -1){
				desc = "More " + all_data[id1].label + " means less " + all_data[id2].label;
			}
			$("#graph_plot .blog_data.large .description").text(desc);
		}
	
	
	
	
	
								
		$(".blog_data input").click(rebuildLineGraph);
		$(".blog_data select").click(rebuildScatterGraph);
		$("#graph_nav select").click(function(){
			if($("#graph_lines").css("display") != "none"){
				rebuildLineGraph();
			}else{
				rebuildScatterGraph();
			}
		});
		
	
	    function showTooltip(x, y, contents) {
	        $('<div id="tooltip">' + contents + '</div>').css( {
	            position: 'absolute',
	            display: 'none',
	            top: y + 5,
	            left: x + 5,
	            border: '1px solid #fdd',
	            padding: '2px',
	            'background-color': '#fee',
	            opacity: 0.80
	        }).appendTo("body").fadeIn(200);
	    }
	
	    
	    var previousPoint = null;
	    $("#graph_lines .blog_stats").bind("plothover", function (event, pos, item) {
	        if (item) {
	            if (previousPoint != item.datapoint) {
	                previousPoint = item.datapoint;
	                
					var labelY = $("#graph_plot .blog_data.small select.y option:selected").text();

	                $("#tooltip").remove();
	                var x = item.datapoint[0].toFixed(0),
	                    y = item.datapoint[1].toFixed(0);
	                    
	                var dt = new Date();
	                dt.setTime(x);
	                
	                showTooltip(item.pageX, item.pageY, (item.series.monthly ? "Month " : "Week") + " of " + monthNames[dt.getMonth()] + (item.series.monthly ? "" : " " + dt.getDate()) + ":<br>" + item.series.label + ": " + y);
	            }
	        }
	        else {
	            $("#tooltip").remove();
	            previousPoint = null;            
	        }
	    });
	    
	    $("#graph_plot .blog_stats").bind("plothover", function (event, pos, item) {
	        if (item) {
	            if (previousPoint != item.datapoint) {
	                previousPoint = item.datapoint;
	                
	                $("#tooltip").remove();
	                var itemX = item.datapoint[0],
	                    itemY = item.datapoint[1];
	                
	                
					var id1 = $("#graph_plot .blog_data.small select.x option:selected").val();
					var id2 = $("#graph_plot .blog_data.small select.y option:selected").val();
					var mindate = $("#graph_nav select.start option:selected").val();
					var maxdate = $("#graph_nav select.end option:selected").val();
					var labelX = $("#graph_plot .blog_data.small select.x option:selected").text();
					var labelY = $("#graph_plot .blog_data.small select.y option:selected").text();
					var dt = null;
                    for (var v in all_data[id1].data){
                    	var x = all_data[id1].data[v];
						var xval = x ? x[1] : 0;
                    	var y = all_data[id2].data[v];
						var yval = y ? y[1] : 0;
						var d = (x ? x[0] : (y ? y[0] : 0));
						
//						var dt = new Date();
//						dt.setTime(d);
						var mindatet = new Date();
						mindatet.setTime(mindate);
						
						if(xval != 0 && yval != 0 &&
							d >= mindate && d <= maxdate){
							if(itemX == xval && itemY == yval){
				                var dt = new Date();
				                dt.setTime(d);
				                break;
				            }
						}
                    }
                    
                    if(!dt) return;
	                
	                
	                
	                showTooltip(item.pageX, item.pageY, "Week of " + monthNames[dt.getMonth()] + " " + dt.getDate() + ":<br>" + labelX + ": " + itemX + "<br>" + labelY + ": " + itemY);
	            }
	        }
	        else {
	            $("#tooltip").remove();
	            previousPoint = null;            
	        }
	    });
	    
	    rebuildLineGraph();
	    
	});
})(jQuery);
</script>