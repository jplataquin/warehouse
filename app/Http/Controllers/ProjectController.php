<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Project::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $projects = $query->get();

        return view('supervisor.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('supervisor.projects.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:projects,name']);
        Project::create($request->all());

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        return view('supervisor.projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate(['name' => 'required|string|max:255|unique:projects,name,'.$project->id]);
        $project->update($request->all());

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    public function show(Project $project)
    {
        return view('supervisor.projects.show', compact('project'));
    }
}
