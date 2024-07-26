<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{

    public function index($id)
    {
        $category = ProjectCategory::query()->findOrFail($id);
        $projects = $category->projects()
            ->where('is_active', true)
            ->orderBy('order', 'desc')
            ->get();
        return view('pages.potfolio.index', compact('category', 'projects'));
    }

    public function all()
    {
        $projects = Project::query()->where('is_active', true)->orderBy('order', 'desc')->get();
        return view('pages.potfolio.index', compact('projects'));
    }

    public function show($slug)
    {
        $project = Project::query()->where('slug', $slug)->firstOrFail();
        $lastProjects = Project::query()->orderBy('order', 'desc')->limit(5)->get();

        $previousProject = Project::query()
            ->where('order', '<', $project->order)
            ->orderBy('order', 'desc')
            ->select('slug')
            ->first();
        $nextProject = Project::query()
            ->where('order', '>', $project->order)
            ->orderBy('order', 'asc')
            ->select('slug')
            ->first();

        return view('pages.potfolio.show', compact('project', 'lastProjects', 'previousProject', 'nextProject'));
    }
}
