<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreListingRequest;
use App\Models\Listing;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stripe\PaymentIntent;
use WhichBrowser\Parser;

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
        })->with('tags')->latest()->get();


        return view('listings.index', compact('listings', 'tags'));

    }
    public function create()
    {
        return view('listings.create');
    }

    /** @var User $user */
    public function store(StoreListingRequest $request)
    {
        $user = Auth::user();
        if (!$user){
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $user->createAsStripeCustomer();

            Auth::login($user);
        }

        try {
            $amount = 9900; // 99 * 100
            if ($request->is_highlighted){
                $amount += 1900; // 19 * 100
            }

            $md = new \ParsedownExtra(); // for markdown

            $listing = $user->listings()
                ->create([
                    'title' => $request->title,
                    'company' => $request->company,
                    'logo' => basename($request->file('logo')->store('public')),
                    'location' => $request->location,
                    'apply_link' => $request->apply_link,
                    'content' => $md->text($request->input('content')),
                    'is_highlighted' => $request->filled('is_highlighted'),
                    'is_active' => true
                ]);

            foreach(explode(',', $request->tags) as $requestTag) {
                $tag = Tag::firstOrCreate([
                    'slug' => Str::slug(trim($requestTag))
                ], [
                    'name' => ucwords(trim($requestTag))
                ]);

                $tag->listings()->attach($listing->id);
            }

            $user->charge($amount, $request->payment_method_id, [
                'return_url' => route('listings.index'),
            ]);

            return redirect()->route('listings.index');
        } catch(\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }

    }

    public function show(Listing $listing)
    {
        return view('listings.show', compact('listing'));
    }

    public function apply(Listing $listing, Request $request)
    {
        $listing->clicks()->create([
            'ipaddress' => $request->ip(),
            'user_agent' => $request->userAgent(),
//            'user_agent' => $request->header('User-Agent'),
        ]);

        return redirect()->to($listing->apply_link);

    }
}
