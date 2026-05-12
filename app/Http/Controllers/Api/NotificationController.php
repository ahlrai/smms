<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->notifications()->latest()->get();
    }

    public function read(Request $request, $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();
        return response()->json(['ok']);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['ok']);
    }
}