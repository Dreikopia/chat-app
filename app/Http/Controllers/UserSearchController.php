<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:20'],
        ]);

        $term = trim($validated['q'] ?? '');

        // Don't search if the query is shorter than 2 characters.
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        // Escape SQL LIKE wildcards so % and _ are treated literally.
        $escapedTerm = strtr(mb_strtolower($term), [
            '!' => '!!',
            '%' => '!%',
            '_' => '!_',
        ]);

        $users = User::query()
            ->select(['id', 'name', 'username', 'avatar_path'])
            ->where('id', '!=', $request->user()->id)
            ->whereRaw(
                "LOWER(username) LIKE ? ESCAPE '!'",
                [$escapedTerm.'%']
            )
            ->orderBy('username')
            ->limit(10)
            ->get();

        return response()->json($users);
    }
}
