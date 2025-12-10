<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>InkWise System - Owner Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  @php
    $ownerCssFiles = [
      'css/owner/appstaff.css',
      'css/owner/orderworkflow.css',
      'css/owner/inventorytrack.css',
      'css/owner/transactionsview.css',
      'css/owner/reports.css',
      'css/owner/index.css',
    ];
  @endphp
  @foreach($ownerCssFiles as $cssPath)
    @php
      $fullPath = public_path($cssPath);
      $version = file_exists($fullPath) ? filemtime($fullPath) : null;
    @endphp
    <link rel="stylesheet" href="{{ asset($cssPath) }}{{ $version ? '?v='.$version : '' }}">
  @endforeach
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-rounded/css/uicons-solid-rounded.css">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-solid-straight/css/uicons-solid-straight.css">
  <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-straight/css/uicons-regular-straight.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  @stack('styles')
  <!-- Topbar styles are provided by /css/owner/index.css (keeps styling centralized). -->
  <script>
    (function() {
      try {
        var isDark = localStorage.getItem('theme') === 'dark';
        if (isDark) {
          document.documentElement.classList.add('dark-mode');
        } else {
          document.documentElement.classList.remove('dark-mode');
        }

        if (document.body) {
          if (isDark) {
            document.body.classList.add('dark-mode');
          } else {
            document.body.classList.remove('dark-mode');
          }
        } else {
          document.addEventListener('DOMContentLoaded', function() {
            if (isDark) {
              document.body.classList.add('dark-mode');
            } else {
              document.body.classList.remove('dark-mode');
            }
          });
        }
      } catch (error) {
        console.error('Theme init error', error);
      }
    })();
  </script>
</head>
<body class="owner-layout">
    @yield('content')
    @stack('scripts')

    <script>
      // Activate admin-like upbar behaviors on owner pages when elements exist
      (function(){
        try {
          // Theme toggle handling
          function initThemeToggle() {
            var themeSwitch = document.getElementById('theme-toggle-switch');
            var themeLabel = document.getElementById('theme-toggle-label');
            var themeIcon = document.getElementById('theme-toggle-icon');
            function setThemeSwitch() {
              if (!themeSwitch) return;
              if (localStorage.getItem('theme') === 'dark' || document.body.classList.contains('dark-mode')) {
                themeSwitch.classList.add('night');
                if (themeLabel) themeLabel.textContent = 'NIGHT';
                if (themeIcon) themeIcon.innerHTML = '<i class="fi fi-rr-moon"></i>';
              } else {
                themeSwitch.classList.remove('night');
                if (themeLabel) themeLabel.textContent = 'DAY';
                if (themeIcon) themeIcon.innerHTML = '<i class="fi fi-rr-brightness"></i>';
              }
            }
            setThemeSwitch();
            if (themeSwitch) {
              themeSwitch.addEventListener('click', function(){
                document.body.classList.toggle('dark-mode');
                if (document.body.classList.contains('dark-mode')) localStorage.setItem('theme','dark'); else localStorage.setItem('theme','light');
                setThemeSwitch();
              });
            }
          }

          // Initialize theme toggle after DOM is loaded
          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initThemeToggle);
          } else {
            initThemeToggle();
          }
        } catch (err) {
          console.error('Owner upbar script error', err);
        }
      })();
    </script>

</body>

</html>
