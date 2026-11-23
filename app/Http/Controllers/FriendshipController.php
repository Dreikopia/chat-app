<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FriendshipController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $friends = $user->friends()
            ->select('id', 'name', 'username', 'avatar_path')
            ->get();

        $incomingRequests = $user->incomingFriendRequests()
            ->with('requester:id,name,username,avatar_path')
            ->latest()
            ->get();

        $outgoingRequests = $user->outgoingFriendRequests()
            ->with('addressee:id,name,username,avatar_path')
            ->latest()
            ->get();

        return Inertia::render('friends/index', [
            'friends' => $friends,
            'incomingRequests' => $incomingRequests,
            'outgoingRequests' => $outgoingRequests,
            'conversations' => $user->conversations(),
            'activeId' => null,
        ]);
    }
}
