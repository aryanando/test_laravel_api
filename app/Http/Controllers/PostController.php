<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::with('user')->latest()->paginate(10);

        $modifiedPosts = collect($posts->items())->map(function ($post) {
            if ($post->video && strlen($post->video) == 11) {
                // Convert YouTube ID into embeddable URL
                $post->video = "https://www.youtube.com/embed/" . $post->video;
            } elseif ($post->video) {
                // Convert uploaded video to full URL
                $post->video = asset('storage/' . $post->video);
            }

            if ($post->image) {
                $post->image = asset('storage/' . $post->image);
            }

            // Add like and dislike count
            $post->like_count = $post->likes()->count();
            $post->dislike_count = 0; // Implement dislikes later if needed

            return $post;
        });

        return response()->json([
            'current_page' => $posts->currentPage(),
            'data' => $modifiedPosts,
            'first_page_url' => $posts->url(1),
            'from' => $posts->firstItem(),
            'last_page' => $posts->lastPage(),
            'last_page_url' => $posts->url($posts->lastPage()),
            'next_page_url' => $posts->nextPageUrl(),
            'per_page' => $posts->perPage(),
            'prev_page_url' => $posts->previousPageUrl(),
            'to' => $posts->lastItem(),
            'total' => $posts->total(),
        ]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'image' => 'nullable|image',
            'video' => 'nullable|mimes:mp4,mov,avi,flv', // Max 20MB for video uploads
            'youtube_link' => 'nullable|url'
        ]);

        $post = new Post();
        $post->user_id = Auth::id();
        $post->content = $request->content;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts', 'public');
            $post->image = $path; // Store only the path
        }

        if ($request->hasFile('video')) {
            $post->video = $request->file('video')->store('videos', 'public');
        } elseif ($request->youtube_link) {
            // Extract YouTube video ID from URL
            $post->video = $this->extractYouTubeID($request->youtube_link);
        }

        $post->save();

        return response()->json(['message' => 'Post created successfully', 'post' => $post->load('user')], 201);
    }

    // Helper function to extract YouTube video ID
    private function extractYouTubeID($url)
    {
        preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=))([\w-]{11})/', $url, $matches);
        return $matches[1] ?? null;
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $user = Auth::user();

        // Check if the user is the owner or an admin
        if ($user->id !== $post->user_id && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated likes
        $post->likes()->delete();

        // Delete associated image file if exists
        if ($post->image) {
            $imagePath = public_path('storage/' . $post->image);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Delete associated video file if exists (only for uploaded videos)
        if ($post->video && strlen($post->video) > 11) { // Ignore YouTube links
            $videoPath = public_path('storage/' . $post->video);
            if (file_exists($videoPath)) {
                unlink($videoPath);
            }
        }

        // Finally, delete the post
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
