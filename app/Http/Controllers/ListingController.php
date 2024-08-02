<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Tag;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $listings = Listing::isActive();
        $allTags = new Tag(['name' => 'all', 'slug' => 'all']);
        $tags = Tag::orderBy('name')->get()->prepend($allTags);

        $listings = $listings->when($request->has('q'), function ($query) use ($request) {
            $searchQuery = trim($request->get('q'));
            $query->where(function ($query) use ($searchQuery) {
                $query->orWhere('title', 'like', "%{$searchQuery}%")
                    ->orWhere('company', 'like', "%{$searchQuery}%")
                    ->orWhere('location', 'like', "%{$searchQuery}%");
            });
        })->when($request->has('tag'), function ($query) use ($request) {
            $searchTag = $request->get('tag');
            if($searchTag != 'all')
            {
                $query->whereHas('tags', function ($query) use ($searchTag) {
                    $query->where('slug', $searchTag);
                });
            }
        })->with('tags')->get();


        return view('listings.index', compact('listings', 'tags'));

    }
    public function create()
    {
        $listings = Listing::isActive()
                    ->with('tags')
                    ->get();

        return view('listings.index', compact('listings'));

    }
}
