<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;

class ArtistController extends Controller
{
    // List all artists
    public function index()
    {
        return Artist::all();
    }

    // Create a new artist
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        $artist = Artist::create($validated);

        return response()->json($artist, 201);
    }

    // Show a single artist
    public function show(Artist $artist)
    {
        return $artist;
    }

    // Update an existing artist
    public function update(Request $request, Artist $artist)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        $artist->update($validated);

        return response()->json($artist, 200);
    }

    // Delete a artist
    public function destroy(Artist $artist)
    {
        $artist->delete();

        return response()->json(null, 204);
    }
}
