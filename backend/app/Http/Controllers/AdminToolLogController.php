<?php

namespace App\Http\Controllers;

use App\Http\Resources\ToolLogResource;
use App\Models\ToolLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminToolLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ToolLog::class);
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        return ToolLogResource::collection(ToolLog::query()->with('user')->latest('created_at')->paginate($perPage)->withQueryString());
    }
}
