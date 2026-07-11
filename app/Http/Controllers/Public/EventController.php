<?php

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Services\BlockHydrator;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    /**
     * Month-grid calendar of published events. `?month=YYYY-MM` selects the
     * month (defaults to the current month); events are placed on their start
     * day in the event's own timezone.
     */
    public function calendar(Request $request): Response
    {
        $month = $this->resolveMonth($request->query('month'));
        $gridStart = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $events = Event::published()->get($this->columns())
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'slug' => $event->slug,
                'date' => $this->displayDate($event)?->toDateString(),
                'time' => $event->all_day || $event->starts_at === null
                    ? null
                    : $event->starts_at->format('g:i A'),
                'all_day' => (bool) $event->all_day,
                'is_virtual' => (bool) $event->is_virtual,
                'location_name' => $event->location_name,
            ])
            ->filter(fn (array $e) => $e['date'] !== null
                && $e['date'] >= $gridStart->toDateString()
                && $e['date'] <= $gridEnd->toDateString())
            ->sortBy(['date', 'time'])
            ->values();

        return $this->render('public/events/calendar', [
            'month' => $month->format('Y-m'),
            'label' => $month->format('F Y'),
            'prev' => $month->copy()->subMonthNoOverflow()->format('Y-m'),
            'next' => $month->copy()->addMonthNoOverflow()->format('Y-m'),
            'today' => today()->toDateString(),
            'gridStart' => $gridStart->toDateString(),
            'gridEnd' => $gridEnd->toDateString(),
            'events' => $events,
            'seo' => $this->seo('Events Calendar'),
        ]);
    }

    private function resolveMonth(mixed $month): CarbonInterface
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
            } catch (\Throwable) {
                // fall through to the current month
            }
        }

        return today()->startOfMonth();
    }

    private function displayDate(Event $event): ?CarbonInterface
    {
        if ($event->all_day) {
            return $event->start_date?->copy();
        }

        // starts_at is stored and shown as the entered wall-clock time (the app
        // runs in UTC and does not shift stored times), so bucket by its date
        // as-is rather than re-projecting through a timezone.
        return $event->starts_at?->copy()->startOfDay();
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
        return ['id', 'title', 'slug', 'starts_at', 'ends_at', 'start_date', 'end_date', 'all_day', 'timezone', 'location_name', 'is_virtual'];
    }

    private function isUpcoming(Event $event): bool
    {
        $end = $event->all_day ? ($event->end_date ?? $event->start_date) : ($event->ends_at ?? $event->starts_at);

        return $end !== null && $end->isFuture();
    }
}
