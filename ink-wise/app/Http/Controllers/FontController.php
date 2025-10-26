<?php

namespace App\Http\Controllers;

use App\Models\Font;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class FontController extends Controller
{
    /**
     * Display a listing of fonts with filtering and search
     */
    public function index(Request $request)
    {
        $query = Font::query();

        // Filter by source
        if ($request->has('source') && $request->source !== 'all') {
            $query->bySource($request->source);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->byCategory($request->category);
        }

        // Search by name
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }

        // Filter active fonts
        if ($request->has('active_only')) {
            $query->active();
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if ($sortBy === 'popular') {
            $query->popular();
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $fonts = $query->paginate(20);

        return response()->json($fonts);
    }

    /**
     * Store a newly uploaded font
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'font_file' => 'required|file|mimes:ttf,otf,woff,woff2|max:5120', // 5MB max
            'category' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('font_file');
            $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9\-_.]/', '', $file->getClientOriginalName());
            $filePath = $file->storeAs('fonts', $fileName, 'public');

            $font = Font::create([
                'name' => $request->name,
                'display_name' => $request->display_name ?: $request->name,
                'source' => 'uploaded',
                'file_path' => $filePath,
                'category' => $request->category ?: 'custom',
                'is_active' => true,
            ]);

            return response()->json($font, 201);
        } catch (\Exception $e) {
            Log::error('Font upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload font'], 500);
        }
    }

    /**
     * Display the specified font
     */
    public function show(Font $font)
    {
        return response()->json($font);
    }

    /**
     * Update the specified font
     */
    public function update(Request $request, Font $font)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $font->update($request->only(['display_name', 'category', 'is_active']));

        return response()->json($font);
    }

    /**
     * Remove the specified font
     */
    public function destroy(Font $font)
    {
        try {
            // Delete file if it's an uploaded font
            if ($font->source === 'uploaded' && $font->file_path) {
                Storage::disk('public')->delete($font->file_path);
            }

            $font->delete();

            return response()->json(['message' => 'Font deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Font deletion failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete font'], 500);
        }
    }

    /**
     * Fetch Google Fonts and store them locally
     */
    public function syncGoogleFonts()
    {
        try {
            $response = Http::get('https://www.googleapis.com/webfonts/v1/webfonts', [
                'key' => config('services.google_fonts.api_key'),
                'sort' => 'popularity'
            ]);

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch Google Fonts'], 500);
            }

            $googleFonts = $response->json()['items'] ?? [];
            $syncedCount = 0;

            foreach ($googleFonts as $googleFont) {
                Font::updateOrCreate(
                    [
                        'google_family' => $googleFont['family'],
                        'source' => 'google'
                    ],
                    [
                        'name' => $googleFont['family'],
                        'display_name' => $googleFont['family'],
                        'variants' => $googleFont['variants'] ?? [],
                        'subsets' => $googleFont['subsets'] ?? [],
                        'category' => $googleFont['category'] ?? 'sans-serif',
                        'is_active' => true,
                    ]
                );
                $syncedCount++;
            }

            return response()->json([
                'message' => "Synced {$syncedCount} Google Fonts successfully"
            ]);
        } catch (\Exception $e) {
            Log::error('Google Fonts sync failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to sync Google Fonts'], 500);
        }
    }

    /**
     * Get font categories
     */
    public function categories()
    {
        $categories = Font::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json($categories);
    }

    /**
     * Record font usage
     */
    public function recordUsage(Request $request, Font $font)
    {
        $font->recordUsage();

        return response()->json(['message' => 'Font usage recorded']);
    }

    /**
     * Get popular fonts
     */
    public function popular(Request $request)
    {
        $limit = $request->get('limit', 10);
        $fonts = Font::popular($limit)->get();

        return response()->json($fonts);
    }
}
