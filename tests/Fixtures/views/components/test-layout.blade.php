@props(['title', 'description' => null, 'ogImage' => null, 'noindex' => false])
<html>
<head>
    <title>{{ $title }}</title>
    @if ($noindex)
        <meta name="robots" content="noindex">
    @endif
</head>
<body>
    <header>{{ $header ?? '' }}</header>
    <main>{{ $slot }}</main>
    <footer>{{ $footer ?? '' }}</footer>
</body>
</html>
