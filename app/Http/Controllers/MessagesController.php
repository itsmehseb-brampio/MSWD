<?php

namespace App\Http\Controllers;

use App\Models\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagesController extends Controller
{
    public function index()
    {
        return view('admin.messages');
    }
}
