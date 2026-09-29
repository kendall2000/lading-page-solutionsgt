{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($enlaces as $e)
    <url>
        <loc>{{ $e['loc'] }}</loc>
@if ($e['lastmod'])
        <lastmod>{{ $e['lastmod']->toAtomString() }}</lastmod>
@endif
        <priority>{{ $e['priority'] }}</priority>
    </url>
@endforeach
</urlset>
