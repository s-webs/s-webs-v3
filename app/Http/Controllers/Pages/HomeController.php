<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $projects = Project::query()->where('is_active', 1)->orderBy('order')->take(6)->get();

        return view('pages.home.index', compact('projects'));
    }
}
