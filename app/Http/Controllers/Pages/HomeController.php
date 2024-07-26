<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $projects = Project::query()->where('is_active', 1)->orderBy('order', 'desc')->take(6)->get();

        return view('pages.home.index', compact('projects'));
    }
}
