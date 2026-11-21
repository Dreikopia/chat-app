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
            ->select('id', 'name', 'username')
            ->get();

        $incomingRequests = $user->incomingFriendRequests()
            ->with('requester:id,name,username')
            ->latest()
            ->get();

        $outgoingRequests = $user->outgoingFriendRequests()
            ->with('addressee:id,name,username')
            ->latest()
            ->get();

        return Inertia::render('Friends/Index', [
            'friends' => $friends,
            'incomingRequests' => $incomingRequests,
            'outgoingRequests' => $outgoingRequests,
            'conversations' => $user->conversations(),
            'activeId' => null,
        ]);
    }
}
