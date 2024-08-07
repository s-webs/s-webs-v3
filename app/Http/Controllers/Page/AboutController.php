<?php

namespace App\Http\Controllers\Page;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;

class AboutController extends Controller
{
    public function index()
    {
        $teams = Team::query()->where('active', true)->get();

        return view('pages.about.index', compact('teams'));
    }

    public function show($slug)
    {
        $team = Team::query()->where('slug', $slug)->firstOrFail();

        return view('pages.about.show', compact('team'));
    }
}
