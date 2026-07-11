<?php

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Services\BlockHydrator;
use Inertia\Response;

class EventController extends PublicController
{
    public function index(): Response
    {
        return $this->render('public/events/index', [
            'upcoming' => Event::published()->upcoming()->orderBy('starts_at')->orderBy('start_date')->get($this->columns()),
            'past' => Event::published()->get($this->columns())
                ->filter(fn (Event $e) => ! $this->isUpcoming($e))
                ->sortByDesc(fn (Event $e) => $e->starts_at ?? $e->start_date)
                ->values(),
            'seo' => $this->seo('Events'),
        ]);
    }

    public function show(string $slug, BlockHydrator $hydrator): Response
    {
        $event = Event::published()->where('slug', $slug)->with('ogMediaAsset.media')->firstOrFail();

        return $this->render('public/events/show', [
            'event' => [
                ...$event->only('id', 'title', 'slug', 'starts_at', 'ends_at', 'start_date', 'end_date', 'all_day', 'timezone', 'location_name', 'address', 'city', 'state', 'zip', 'is_virtual', 'virtual_url', 'registration_url'),
                'description' => $hydrator->hydrate($event->description ?? []),
            ],
            'seo' => $this->seo($event->seo_title ?: $event->title, $event->seo_description, $event->ogMediaAsset?->url),
        ]);
    }

    /** @return list<string> */
    private function columns(): array
    {
        return ['id', 'title', 'slug', 'starts_at', 'ends_at', 'start_date', 'end_date', 'all_day', 'location_name', 'is_virtual'];
    }

    private function isUpcoming(Event $event): bool
    {
        $end = $event->all_day ? ($event->end_date ?? $event->start_date) : ($event->ends_at ?? $event->starts_at);

        return $end !== null && $end->isFuture();
    }
}
