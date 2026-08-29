<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportIndexController extends Controller
{
    /**
     * Show the reports dashboard.
     */
    public function index()
    {
        return view('reports.index');
    }
}
