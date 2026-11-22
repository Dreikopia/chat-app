<?php

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FriendRequestController extends Controller
{
    public function __construct(
        private FriendshipService $friendshipService
    ) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        $this->friendshipService->sendRequest(
            $request->user(),
            $user
        );

        return back()->with('success', 'Friend request sent.');
    }

    public function accept(Request $request, Friendship $friendship): RedirectResponse
    {
        $this->friendshipService->acceptRequest(
            $friendship,
            $request->user()
        );

        return back()->with('success', 'Friend request accepted.');
    }

    public function decline(Request $request, Friendship $friendship): RedirectResponse
    {
        $this->friendshipService->declineRequest(
            $friendship,
            $request->user()
        );

        return back()->with('success', 'Friend request declined.');
    }

    public function cancel(Request $request, Friendship $friendship): RedirectResponse
    {
        $this->friendshipService->cancelRequest(
            $friendship,
            $request->user()
        );

        return back()->with('success', 'Friend request cancelled.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $friendship = Friendship::query()
            ->where(function ($query) use ($request, $user) {
                $query->where(function ($query) use ($request, $user) {
                    $query->where('requester_id', $request->user()->id)
                        ->where('addressee_id', $user->id);
                })->orWhere(function ($query) use ($request, $user) {
                    $query->where('requester_id', $user->id)
                        ->where('addressee_id', $request->user()->id);
                });
            })
            ->where('status', FriendshipStatus::ACCEPTED)
            ->first();

        if ($friendship) {
            $this->friendshipService->removeFriend($friendship, $request->user());
        }

        return back()->with('success', 'Friend removed.');
    }
}
