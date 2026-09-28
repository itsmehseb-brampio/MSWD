<?php

namespace App\Http\Controllers;

use App\Models\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagesController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin->hasPerm('manage_messages')) {
            abort(403, 'You do not have permission to access messages.');
        }

        return view('admin.messages');
    }
}
