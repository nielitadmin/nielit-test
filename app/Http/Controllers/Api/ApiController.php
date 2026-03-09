<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function index()
    {
        return response()->json([
            'api_name' => "QwikTest API v1.0",
            'api_version' => "1.0"
        ], 200);
    }
}
