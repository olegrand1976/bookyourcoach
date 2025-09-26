<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function dashboard()
    {
        return response()->json(['message' => 'Welcome to the teacher dashboard']);
    }
}
