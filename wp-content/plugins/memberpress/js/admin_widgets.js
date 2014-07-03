google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);

function drawChart() {
  var loadurl = jQuery('div#mepr-widget-loadurl').attr('data-value');
  var currency_symbol = jQuery('div#mepr-widget-currency-symbol').attr('data-value');
  
  //Weekly stats
  var weeklyChartJsonData = jQuery.ajax({
    url: loadurl+"loadwidget",
    dataType: "json",
    async: false
  }).responseText;
  
  var weeklyChartData = new google.visualization.DataTable(weeklyChartJsonData);
  
  var weeklyChart = new google.visualization.AreaChart(document.getElementById('mepr-widget-report'));
  weeklyChart.draw(weeklyChartData, {vAxis: {format: currency_symbol}});
}
