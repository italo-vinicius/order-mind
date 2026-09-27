<?php

use App\AI\Contracts\AssistantProvider;
use App\AI\Contracts\AssistantResponse;
use App\AI\Exceptions\AssistantUnavailableException;
use App\AI\GeminiAssistantProvider;
use App\AI\Tools\ToolRegistry;
use App\AI\UnavailableAssistantProvider;
use App\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ToolLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('authorized tools return only the authenticated customer data', function (): void {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $latest = Order::factory()->for($customer)->create(['number' => 'OM-MINE-002', 'placed_at' => now(), 'total_amount' => 30, 'status' => OrderStatus::Processing, 'cancellable_until' => now()->addDay()]);
    OrderItem::factory()->for($latest)->create(['product_name' => 'Fone de ouvido']);
    Order::factory()->for($customer)->create(['number' => 'OM-MINE-001', 'placed_at' => now()->subDay(), 'total_amount' => 20, 'estimated_delivery_at' => now()->subHour(), 'status' => OrderStatus::Shipped]);
    $foreign = Order::factory()->for($other)->create(['number' => 'OM-OTHER-001', 'estimated_delivery_at' => now()->subHour(), 'status' => OrderStatus::Shipped]);
    $tools = app(ToolRegistry::class);

    expect($tools->execute($customer, 'get_latest_order', [])['order']['number'])->toBe('OM-MINE-002')
        ->and($tools->execute($customer, 'get_order_details', ['order_number' => 'OM-OTHER-001'])['order'])->toBeNull()
        ->and($tools->execute($customer, 'list_delayed_orders', [])['count'])->toBe(1)
        ->and($tools->execute($customer, 'calculate_monthly_spending', ['month' => now('America/Sao_Paulo')->format('Y-m')])['total_amount'])->toBe('50.00')
        ->and($tools->execute($customer, 'check_cancellation_eligibility', ['order_number' => $latest->number])['eligible'])->toBeTrue()
        ->and($foreign->user_id)->not->toBe($customer->id);
});

test('conversation message orchestrates a tool and persists sanitized audit data', function (): void {
    $customer = User::factory()->create();
    Order::factory()->for($customer)->create(['number' => 'OM-MINE-001']);
    $this->app->instance(AssistantProvider::class, new ScriptedAssistantProvider([
        new AssistantResponse('', [['id' => 'call-1', 'name' => 'get_latest_order', 'arguments' => []]]),
        new AssistantResponse('Seu pedido mais recente foi localizado.'),
    ]));

    $conversation = $this->actingAs($customer, 'sanctum')->postJson('/api/conversations')->assertCreated()->json('data');
    $this->postJson('/api/conversations/'.$conversation['id'].'/messages', ['content' => 'Qual é meu pedido mais recente?'])
        ->assertOk()->assertJsonPath('data.role', 'assistant')->assertJsonPath('data.content', 'Seu pedido mais recente foi localizado.');

    expect(ToolLog::query()->where('user_id', $customer->id)->firstOrFail())
        ->tool_name->toBe('get_latest_order')
        ->input->toBe([])
        ->output->not->toHaveKey('shipping_address');
    expect(Conversation::findOrFail($conversation['id'])->messages)->toHaveCount(2);
});

test('conversation and tool logs enforce their policies', function (): void {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $conversation = Conversation::factory()->for($customer)->create();
    ToolLog::factory()->for($customer)->for($conversation)->create();

    $this->actingAs($other, 'sanctum')->getJson('/api/conversations/'.$conversation->id)->assertForbidden();
    $this->actingAs($customer, 'sanctum')->getJson('/api/admin/ai-tool-logs')->assertForbidden();
    $this->actingAs($admin, 'sanctum')->getJson('/api/admin/ai-tool-logs')->assertOk()->assertJsonPath('data.0.tool_name', 'get_latest_order');
});

test('invalid and unknown tool calls are logged without bypassing authorization', function (): void {
    $customer = User::factory()->create();
    $this->app->instance(AssistantProvider::class, new ScriptedAssistantProvider([
        new AssistantResponse('', [['id' => 'call-1', 'name' => 'get_order_details', 'arguments' => []]]),
        new AssistantResponse('', [['id' => 'call-2', 'name' => 'delete_everything', 'arguments' => ['user_id' => 999]]]),
        new AssistantResponse('Não encontrei um pedido para essa consulta.'),
    ]));
    $conversation = Conversation::factory()->for($customer)->create();

    $this->actingAs($customer, 'sanctum')->postJson('/api/conversations/'.$conversation->id.'/messages', ['content' => 'Teste'])->assertOk();

    expect(ToolLog::query()->where('conversation_id', $conversation->id)->where('succeeded', false)->count())->toBe(2);
});

test('provider unavailability and tool-call limits have controlled responses', function (): void {
    $customer = User::factory()->create();
    $conversation = Conversation::factory()->for($customer)->create();
    $this->app->instance(AssistantProvider::class, new UnavailableAssistantProvider);
    $this->actingAs($customer, 'sanctum')->postJson('/api/conversations/'.$conversation->id.'/messages', ['content' => 'Olá'])->assertStatus(503);
    expect($conversation->fresh()->messages)->toHaveCount(1);

    config()->set('services.gemini.max_tool_iterations', 1);
    $this->app->instance(AssistantProvider::class, new ScriptedAssistantProvider([
        new AssistantResponse('', [['id' => 'call-1', 'name' => 'get_latest_order', 'arguments' => []]]),
    ]));
    $this->actingAs($customer, 'sanctum')->postJson('/api/conversations/'.$conversation->id.'/messages', ['content' => 'Olá de novo'])->assertUnprocessable();
    expect(ToolLog::query()->where('conversation_id', $conversation->id)->count())->toBe(1);
});

test('gemini client uses the generate content contract without managed tools', function (): void {
    config()->set('services.gemini.api_key', 'test-key');
    config()->set('services.gemini.model', 'gemini-3.7-flash');
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent' => Http::response([
            'candidates' => [['content' => ['parts' => [['functionCall' => ['name' => 'get_latest_order', 'args' => []]]]]]],
        ]),
    ]);

    $response = app(GeminiAssistantProvider::class)->respond([['role' => 'user', 'parts' => [['text' => 'Meu último pedido']]]], app(ToolRegistry::class)->definitions());

    expect($response->toolCalls[0]['name'])->toBe('get_latest_order');
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent'
        && $request->hasHeader('x-goog-api-key', 'test-key')
        && count($request['tools'][0]['functionDeclarations']) === 5
        && ! array_key_exists('google_search', $request->data()));
});

test('gemini client turns quota and connection failures into controlled errors', function (): void {
    config()->set('services.gemini.api_key', 'test-key');
    config()->set('services.gemini.model', 'gemini-3.7-flash');
    Http::fake(['https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent' => Http::response([], 429)]);

    expect(fn () => app(GeminiAssistantProvider::class)->respond([], []))->toThrow(AssistantUnavailableException::class);

    Http::fake(['https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent' => Http::failedConnection()]);
    expect(fn () => app(GeminiAssistantProvider::class)->respond([], []))->toThrow(AssistantUnavailableException::class, 'demorou para responder');
});

class ScriptedAssistantProvider implements AssistantProvider
{
    /** @param  array<int, AssistantResponse>  $responses */
    public function __construct(private array $responses) {}

    public function respond(array $contents, array $tools, ?string $previousInteractionId = null, array $toolResults = []): AssistantResponse
    {
        return array_shift($this->responses) ?? new AssistantResponse('Sem resposta.');
    }

    public function generateTitle(string $firstMessage): string
    {
        return 'Título de teste';
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
