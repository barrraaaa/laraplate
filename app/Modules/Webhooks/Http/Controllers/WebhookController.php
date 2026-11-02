<?php

namespace App\Modules\Webhooks\Http\Controllers;

use App\Models\Organization;
use App\Modules\Core\FeatureManager;
use App\Modules\Tenancy\OrganizationContext;
use App\Modules\Webhooks\Jobs\DeliverWebhookJob;
use App\Modules\Webhooks\Models\WebhookDelivery;
use App\Modules\Webhooks\Models\WebhookEndpoint;
use App\Modules\Webhooks\Services\WebhookDispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Customer webhook management (PRD 5.8 P1): endpoint registration,
 * subscriptions, secret rotation, delivery log and manual retries —
 * scoped to the active organization.
 */
class WebhookController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private WebhookDispatcher $dispatcher) {}

    public function index(Request $request): Response
    {
        app(FeatureManager::class)->require('webhooks');

        $organization = OrganizationContext::organization();

        return Inertia::render('app/Settings/Webhooks', [
            'endpoints' => WebhookEndpoint::query()
                ->where('organization_id', $organization->id)
                ->withCount('deliveries')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (WebhookEndpoint $endpoint) => $this->serialize($endpoint)),
            'deliveries' => WebhookDelivery::query()
                ->whereIn('endpoint_id', WebhookEndpoint::query()
                    ->where('organization_id', $organization->id)
                    ->select('id'))
                ->with('endpoint:id,url')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn (WebhookDelivery $delivery) => [
                    'id' => $delivery->id,
                    'endpoint' => $delivery->endpoint?->url,
                    'event' => $delivery->event,
                    'attemptCount' => $delivery->attempt_count,
                    'responseCode' => $delivery->response_code,
                    'delivered' => $delivery->delivered_at !== null,
                    'createdAt' => $delivery->created_at?->toIso8601String(),
                ]),
            'eventCatalog' => WebhookDispatcher::eventCatalog(),
            'canManage' => $request->user()->isOrganizationAdmin($organization),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        app(FeatureManager::class)->require('webhooks');

        $organization = OrganizationContext::organization();
        $this->authorizeManage($request, $organization);

        $data = $request->validate([
            'url' => ['required', 'url', 'max:1024'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', array_keys(config('saas.webhooks.events', [])))],
        ]);

        WebhookEndpoint::query()->create([
            'organization_id' => $organization->id,
            'url' => $data['url'],
            'secret' => $this->dispatcher->generateSecret(),
            'events' => array_values($data['events']),
            'active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'webhook-created');
    }

    public function update(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        app(FeatureManager::class)->require('webhooks');

        $this->authorizeManage($request, $endpoint->organization);

        $data = $request->validate([
            'url' => ['sometimes', 'required', 'url', 'max:1024'],
            'events' => ['sometimes', 'required', 'array', 'min:1'],
            'events.*' => ['string', 'in:'.implode(',', array_keys(config('saas.webhooks.events', [])))],
            'active' => ['sometimes', 'boolean'],
        ]);

        $endpoint->update($data);

        return back()->with('status', 'webhook-updated');
    }

    public function regenerateSecret(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        app(FeatureManager::class)->require('webhooks');

        $this->authorizeManage($request, $endpoint->organization);

        $endpoint->forceFill(['secret' => $this->dispatcher->generateSecret()])->save();

        return back()->with('status', 'webhook-secret-rotated');
    }

    public function destroy(Request $request, WebhookEndpoint $endpoint): RedirectResponse
    {
        app(FeatureManager::class)->require('webhooks');

        $this->authorizeManage($request, $endpoint->organization);

        $endpoint->delete();

        return back()->with('status', 'webhook-deleted');
    }

    public function retry(Request $request, WebhookDelivery $delivery): RedirectResponse
    {
        app(FeatureManager::class)->require('webhooks');

        $organization = OrganizationContext::organization();
        $this->authorizeManage($request, $organization);

        abort_unless($delivery->endpoint?->organization_id === $organization->id, 404);

        DeliverWebhookJob::dispatch($delivery);

        return back()->with('status', 'webhook-retried');
    }

    private function authorizeManage(Request $request, Organization $organization): void
    {
        abort_unless($request->user()->isOrganizationAdmin($organization), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(WebhookEndpoint $endpoint): array
    {
        return [
            'id' => $endpoint->id,
            'url' => $endpoint->url,
            'events' => $endpoint->events,
            'active' => $endpoint->active,
            'deliveriesCount' => $endpoint->deliveries_count ?? 0,
            'createdAt' => $endpoint->created_at?->toIso8601String(),
        ];
    }
}
