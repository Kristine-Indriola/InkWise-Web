<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GraphicsProxyController extends Controller
{
    /**
     * Proxy to SVGRepo search API to avoid CORS and expose no client keys.
     */
    public function svgrepo(Request $request)
    {
        $q = $request->query('q', '');
        $limit = (int) $request->query('limit', 12);
        $limit = $limit > 0 ? min(50, $limit) : 12;

        if ($q === '') {
            return response()->json(['results' => []]);
        }

        $cacheKey = 'svgrepo_search_' . md5($q . '|' . $limit);
        $cached = cache()->get($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        $url = 'https://api.svgrepo.com/v2/search/?q=' . urlencode($q) . '&limit=' . $limit;
        $resp = Http::get($url);

        $body = null;
        try {
            $body = $resp->json();
        } catch (\Exception $e) {
            $body = ['results' => []];
        }

        // cache for 10 minutes
        cache()->put($cacheKey, $body, now()->addMinutes(10));

        return response()->json($body, $resp->status());
    }

    /**
     * Proxy to Unsplash search (requires UNSPLASH_ACCESS_KEY in .env)
     */
    public function unsplash(Request $request)
    {
        $q = $request->query('q', '');
        $per_page = (int) $request->query('per_page', 12);
        $per_page = $per_page > 0 ? min(30, $per_page) : 12;

        $key = env('UNSPLASH_ACCESS_KEY');
        if (!$key) {
            return response()->json(['error' => 'UNSPLASH_ACCESS_KEY not set on server'], 500);
        }

        $url = 'https://api.unsplash.com/search/photos?query=' . urlencode($q) . '&per_page=' . $per_page;
        $resp = Http::withHeaders([
            'Accept-Version' => 'v1',
            'Authorization' => 'Client-ID ' . $key,
        ])->get($url);

        return response($resp->body(), $resp->status())
            ->header('Content-Type', $resp->header('Content-Type', 'application/json'));
    }
}
