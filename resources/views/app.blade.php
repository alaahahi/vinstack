<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KAML KAMAL</title>
    <meta name="description" content="KAML KAMAL — Fast. Safe. Reliable.">
    <meta name="application-name" content="KAML KAMAL">
    <script>
        (function () {
            var t = localStorage.getItem('theme');
            document.documentElement.setAttribute('data-theme', t === 'dark' ? 'dark' : 'light');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
