<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System - Owner Dashboard</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="/css/owner/index.css">
  <link rel="stylesheet" href="/css/owner/appstaff.css">
  <link rel="stylesheet" href="/css/owner/orderworkflow.css">
  <link rel="stylesheet" href="/css/owner/inventorytrack.css">
  <link rel="stylesheet" href="/css/owner/transactionsview.css">
  <link rel="stylesheet" href="/css/owner/reports.css">
</head>
<body>
    @yield('content')
      <script>
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Invitation - Birthday Party','Keychain','Invitation - Floral Pink'],
        datasets: [{
          label: 'Units Sold',
          data: [12, 15, 20],
          backgroundColor: ['#68b4e3ff','#4487daff','#1147dbff'],
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        layout: { padding: { bottom: 30 } }, /* make room for labels */
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: '#eef2f7' }
          },
          x: {
            grid: { display: false },
            ticks: {
              autoSkip: false,         /* keep all labels */
              maxRotation: 0,          /* no tilt */
              minRotation: 0,
              align: 'center',
              callback: function(value){
                const label = this.getLabelForValue(value);
                return label.length > 22 ? label.slice(0,22) + '…' : label; /* truncate if too long */
              }
            }
          }
        }
      }
    });

    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
      type: 'line',
      data: { labels: ['Week 1','Week 2','Week 3','Week 4'],
        datasets: [
          { label: 'Incoming Stock', data: [20,40,25,35], borderColor: '#16a34a', fill:false, tension:.3 },
          { label: 'Outgoing Stock', data: [70,30,20,50], borderColor: '#ef4444', fill:false, tension:.3 }
        ]},
      options: { responsive: true, maintainAspectRatio: false }
    });
  </script>
  
</body>

</html>
