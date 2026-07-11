<?php

namespace App\Http\Controllers\Public;

use App\Models\TeamMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Response;

class TeamController extends PublicController
{
    public function index(): Response
    {
        $members = $this->activeMembers();

        return $this->render('public/team/index', [
            'groups' => [
                ['key' => 'staff', 'label' => 'Staff', 'members' => $this->cards($members->where('group', 'staff')->values())],
                ['key' => 'board', 'label' => 'Board', 'members' => $this->cards($members->where('group', 'board')->values())],
            ],
            'seo' => $this->seo('Our Team', 'Meet the staff and board of the Native Dads Network.'),
        ]);
    }

    public function show(string $slug): Response
    {
        $members = $this->activeMembers();
        $member = $members->firstWhere('slug', $slug);

        abort_if($member === null, 404);

        // Cycle within the member's own group (staff or board).
        $group = $members->where('group', $member->group)->values();
        $index = $group->search(fn (TeamMember $m) => $m->id === $member->id);
        $prev = $index > 0 ? $group[$index - 1] : $group->last();
        $next = $index < $group->count() - 1 ? $group[$index + 1] : $group->first();

        $ogImage = $member->og_image_path ? asset('storage/'.$member->og_image_path) : $member->photo?->url;

        return $this->render('public/team/show', [
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'slug' => $member->slug,
                'title' => $member->title,
                'group' => $member->group,
                'bio' => $member->bio,
                'photo_url' => $member->photo?->url,
                'email' => $member->show_email ? $member->email : null,
                'phone' => $member->show_phone ? $member->phone : null,
            ],
            'siblings' => [
                'prev' => $prev && $prev->id !== $member->id ? ['name' => $prev->name, 'slug' => $prev->slug] : null,
                'next' => $next && $next->id !== $member->id ? ['name' => $next->name, 'slug' => $next->slug] : null,
            ],
            'seo' => $this->seo($member->name.' — '.$member->title, $this->excerpt($member->bio), $ogImage),
        ]);
    }

    /** @return Collection<int, TeamMember> */
    private function activeMembers(): Collection
    {
        return TeamMember::where('is_active', true)
            ->with('photo.media')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  Collection<int, TeamMember>  $members
     * @return array<int, array<string, mixed>>
     */
    private function cards(Collection $members): array
    {
        return $members->map(function (TeamMember $m) {
            $photo = $m->photo;

            return [
                'id' => $m->id,
                'name' => $m->name,
                'slug' => $m->slug,
                'title' => $m->title,
                'photo_url' => $photo ? ($photo->thumb_url ?? $photo->url) : null,
            ];
        })->all();
    }

    private function excerpt(?string $bio): ?string
    {
        if ($bio === null || $bio === '') {
            return null;
        }

        return Str::limit(trim(preg_replace('/\s+/', ' ', $bio) ?? ''), 155);
    }
}
