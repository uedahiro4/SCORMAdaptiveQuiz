function dispPct2(numtxt,valtxt,ctx,config,posX,posY,borderX,borderY,overlay,data,animPC){
        return(Math.round(animPC*data[0].value));
} 

function rinrinchart(skip,nonskip,skipLabel,nonskipLabel){
	var mydata4 = [
	{
		value : skip,
		color: "#99CC33",
		title : skipLabel,
		shapesInChart : [
			{
				position : "RELATIVE",
				shape : "TEXT",
				text : dispPct2,
				x1 : 2,
				y1 : 2,
				textAlign : "center",
				textBaseline : "middle",
				fontColor : "#99CC33", 
				fontSize : 50
			}
		]

	},
	{
		value : nonskip,
		color: "#F7464A",
		title : nonskipLabel
	}
]
var crosstxt4 = {
	legend : true,
	animationEasing : "linear",
	endDrawDataFunction: drawShapes,
	canvasBorders : false,
	canvasBordersWidth : 3,
	canvasBordersColor : "black",
	graphTitleFontFamily : "'Arial'",
	graphTitleFontSize : 18,
	graphTitleFontStyle : "bold",
	graphTitleFontColor : "#666",
	spaceTop : 30,
	spaceBottom : 30,
	spaceLeft : 30,
	spaceRight : 30,
	startAngle : 180,  
	dynamicDisplay : true                
}
	var myLine = new Chart(document.getElementById("chart-area").getContext("2d")).Doughnut(mydata4,crosstxt4);
	pushInGraphData("Doughnut",mydata4,crosstxt4,{text : "<Title> (<Value>)",fontSize : 18});
};

/*
function rinrinchart(skip,nonskip,skipLabel,nonskipLabel){
	var segments = [
		{
			value: skip,
			color: "#99CC33",
			label: skipLabel
		},
		{
			value: nonskip,
			color:"#F7464A",
			label: nonskipLabel
		}
	];
	var options = {
		responsive : true,
        // 凡例表示用の HTML テンプレート
		legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"><%if(segments[i].label){%><%=segments[i].label%><%}%>&nbsp;:&nbsp;&nbsp;<%if(segments[i].value){%><%=segments[i].value%><%}%></span></li><%}%></ul>"
    };
	var ctx = document.getElementById("chart-area").getContext("2d");
	var chart = new Chart(ctx).Doughnut(segments,options);
	document.getElementById('chart_legend').innerHTML = chart.generateLegend();
};
*/