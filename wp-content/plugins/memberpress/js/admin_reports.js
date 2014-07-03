google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);

var main_view = jQuery('div#mepr-reports-main-view').attr('data-value');
var currency_symbol = jQuery('div#mepr-reports-currency-symbol').attr('data-value');

function drawChart() {
  var loadurl = jQuery('div#mepr-reports-loadurl').attr('data-value');
  var month = jQuery('div#monthly-dropdowns-form select[name="month"]').val();
  var year = jQuery('div#monthly-dropdowns-form select[name="year"]').val();
  var product = jQuery('div#monthly-dropdowns-form select[name="product"]').val();
  
  //Monthly Amounts Area Chart
  var monthlyAmountsChartJsonData = jQuery.ajax({
    url: loadurl+"loadmonth&type=amounts&month="+month+"&year="+year+"&product="+product,
    dataType: "json",
    async: false
  }).responseText;
  
  var monthlyAmountsChartData = new google.visualization.DataTable(monthlyAmountsChartJsonData);
  
  var monthlyAmountsChart = new google.visualization.AreaChart(document.getElementById('monthly-amounts-area-graph'));
  
  //NOT WORKING
  // var monthlyAmountsFormatter = new google.visualization.NumberFormat({fractionDigits: 2});
  // monthlyAmountsFormatter.format(MonthlyAmountsChartData, 2);
  
  monthlyAmountsChart.draw(monthlyAmountsChartData, {height: '350', width: (jQuery('div#'+main_view+'-reports-area').width() - 55), title: jQuery('div#mepr-reports-monthly-areas-title').attr('data-value'), hAxis: {title: jQuery('div#mepr-reports-monthly-htitle').attr('data-value')}, vAxis: {format: currency_symbol}});
  
  //Yearly Amounts Area Chart
  var yearlyAmountsChartJsonData = jQuery.ajax({
    url: loadurl+"loadyear&type=amounts&year="+year+"&product="+product,
    dataType: "json",
    async: false
  }).responseText;
  
  var yearlyAmountsChartData = new google.visualization.DataTable(yearlyAmountsChartJsonData);
  
  var yearlyAmountsChart = new google.visualization.AreaChart(document.getElementById('yearly-amounts-area-graph'));
  yearlyAmountsChart.draw(yearlyAmountsChartData, {height: '350', width: (jQuery('div#'+main_view+'-reports-area').width() - 55), title: jQuery('div#mepr-reports-yearly-areas-title').attr('data-value'), hAxis: {title: jQuery('div#mepr-reports-yearly-htitle').attr('data-value')}, vAxis: {format: currency_symbol}});
  
  //Monthly Transactions Area Chart
  var monthlyTransactionsChartJsonData = jQuery.ajax({
    url: loadurl+"loadmonth&type=transactions&month="+month+"&year="+year+"&product="+product,
    dataType: "json",
    async: false
  }).responseText;
  
  var monthlyTransactionsChartData = new google.visualization.DataTable(monthlyTransactionsChartJsonData);
  
  var monthlyTransactionsChart = new google.visualization.AreaChart(document.getElementById('monthly-transactions-area-graph'));
  monthlyTransactionsChart.draw(monthlyTransactionsChartData, {height: '350', width: (jQuery('div#'+main_view+'-reports-area').width() - 55), title: jQuery('div#mepr-reports-monthly-transactions-title').attr('data-value'), hAxis: {title: jQuery('div#mepr-reports-monthly-htitle').attr('data-value')}});
  
  //Yearly Transactions Area Chart
  var yearlyTransactionsChartJsonData = jQuery.ajax({
    url: loadurl+"loadyear&type=transactions&year="+year+"&product="+product,
    dataType: "json",
    async: false
  }).responseText;
  
  var yearlyTransactionsChartData = new google.visualization.DataTable(yearlyTransactionsChartJsonData);
  
  var yearlyTransactionsChart = new google.visualization.AreaChart(document.getElementById('yearly-transactions-area-graph'));
  yearlyTransactionsChart.draw(yearlyTransactionsChartData, {height: '350', width: (jQuery('div#'+main_view+'-reports-area').width() - 55), title: jQuery('div#mepr-reports-yearly-transactions-title').attr('data-value'), hAxis: {title: jQuery('div#mepr-reports-yearly-htitle').attr('data-value')}});

  //Monthly Pie Chart Totals
  var monthlyPieChartJsonData = jQuery.ajax({
    url: loadurl+"loadpie&type=monthly&month="+month+"&year="+year,
    dataType: "json",
    async: false
  }).responseText;
  
  var monthlyPieChartData = new google.visualization.DataTable(monthlyPieChartJsonData);
  
  var monthlyPieChart = new google.visualization.PieChart(document.getElementById('monthly-pie-chart-area'));
  monthlyPieChart.draw(monthlyPieChartData, {height: 185, width: 330, title: jQuery('div#mepr-reports-pie-title').attr('data-value')});
  
  //Yearly Pie Chart Totals
  var yearlyPieChartJsonData = jQuery.ajax({
    url: loadurl+"loadpie&type=yearly&year="+year,
    dataType: "json",
    async: false
  }).responseText;
  
  var yearlyPieChartData = new google.visualization.DataTable(yearlyPieChartJsonData);
  
  var yearlyPieChart = new google.visualization.PieChart(document.getElementById('yearly-pie-chart-area'));
  yearlyPieChart.draw(yearlyPieChartData, {height: 185, width: 330, title: jQuery('div#mepr-reports-pie-title').attr('data-value')});
  
  //All-Time Pie Chart Totals
  var alltimePieChartJsonData = jQuery.ajax({
    url: loadurl+"loadpie&type=all-time",
    dataType: "json",
    async: false
  }).responseText;
  
  var alltimePieChartData = new google.visualization.DataTable(alltimePieChartJsonData);
  
  var alltimePieChart = new google.visualization.PieChart(document.getElementById('all-time-pie-chart-area'));
  alltimePieChart.draw(alltimePieChartData, {height: 185, width: 330, title: jQuery('div#mepr-reports-pie-title').attr('data-value')});
}

(function($) {
  $(document).ready(function() {
    //SHOW CHOSEN AREA
    $('.main-nav-tab').removeClass('nav-tab-active');
    $('a#'+main_view).addClass('nav-tab-active');
    $('div#'+main_view+'-reports-area').show();
    $('div#monthly-amounts-area-graph').show();
    $('div#yearly-amounts-area-graph').show();
    
    //MAIN NAV TABS CONTROL
    $('a.main-nav-tab').click(function() {
      if($(this).hasClass('nav-tab-active'))
        return false;
      
      var chosen = $(this).attr('id');
      
      $('a.main-nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      
      $('div.mepr_reports_area').hide();
      $('div.' + chosen).show();
      
      return false;
    });
    
    //MONTHLY NAV TABS CONTROL
    $('a.monthly-nav-tab').click(function() {
      if($(this).hasClass('nav-tab-active'))
        return false;
      
      var chosen = $(this).attr('id');
      
      $('a.monthly-nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      
      $('div.monthly_graph_area').hide();
      $('div.' + chosen).show();
      
      return false;
    });
    
    //YEARLY NAV TABS CONTROL
    $('a.yearly-nav-tab').click(function() {
      if($(this).hasClass('nav-tab-active'))
        return false;
      
      var chosen = $(this).attr('id');
      
      $('a.yearly-nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      
      $('div.yearly_graph_area').hide();
      $('div.' + chosen).show();
      
      return false;
    });
    
  });
})(jQuery);
