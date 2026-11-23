<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FriendshipController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $friends = $user->friends()
            ->select('id', 'name', 'username', 'avatar_path', 'last_seen_at')
            ->get()
            ->map(function (User $friend) {
                $friend->setAttribute('is_active', $friend->isActive());

                return $friend;
            });

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
