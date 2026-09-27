<?php

namespace App\Http\Controllers;

use App\AI\Exceptions\AssistantLimitExceededException;
use App\AI\Exceptions\AssistantUnavailableException;
use App\AI\OrderAssistant;
use App\Http\Requests\StoreConversationMessageRequest;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Conversation::class);

        return ConversationResource::collection($request->user()->conversations()->latest('updated_at')->paginate(15));
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $this->authorize('create', Conversation::class);
        $conversation = $request->user()->conversations()->create(['title' => $request->validated('title') ?: 'Nova conversa']);

        return (new ConversationResource($conversation))->response()->setStatusCode(201);
    }

    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorize('view', $conversation);

        return new ConversationResource($conversation->load(['messages' => fn ($query) => $query->oldest('id')]));
    }

    public function storeMessage(StoreConversationMessageRequest $request, Conversation $conversation, OrderAssistant $assistant): JsonResponse
    {
        $this->authorize('update', $conversation);
        try {
            return (new MessageResource($assistant->respond($request->user(), $conversation, $request->validated('content'))))->response()->setStatusCode(200);
        } catch (AssistantUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        } catch (AssistantLimitExceededException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
