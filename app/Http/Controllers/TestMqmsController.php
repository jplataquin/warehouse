<?php

namespace App\Http\Controllers;

use App\Services\MqmsApiClient;
use Illuminate\Http\Request;

class TestMqmsController extends Controller
{
    protected $mqmsClient;

    public function __construct(MqmsApiClient $mqmsClient)
    {
        $this->mqmsClient = $mqmsClient;
    }

    public function projects()
    {
        try {
            $projects = $this->mqmsClient->getProjects(['status' => 'PEND']);
            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch projects',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
