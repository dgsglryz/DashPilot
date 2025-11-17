<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @if(app()->environment('production'))
            @php
                $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
                $entry = $manifest['resources/js/app.ts'] ?? null;
            @endphp
            @if($entry)
                @if(isset($entry['css']) && is_array($entry['css']))
                    @foreach($entry['css'] as $css)
                        <link rel="stylesheet" href="{{ asset('build/' . $css) }}">
                    @endforeach
                @endif
                <script type="module" src="{{ asset('build/' . $entry['file']) }}"></script>
            @endif
        @else
            @vite('resources/js/app.ts')
        @endif
        @inertiaHead
        
        <!-- Alpine.js for lightweight interactions -->
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
