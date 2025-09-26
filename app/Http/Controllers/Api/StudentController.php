<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the student dashboard']);
    }
}
