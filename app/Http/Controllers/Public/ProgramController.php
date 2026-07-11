<?php

namespace App\Http\Controllers\Public;

use App\Models\Program;
use App\Services\BlockHydrator;
use Inertia\Response;

class ProgramController extends PublicController
{
    public function index(): Response
    {
        return $this->render('public/programs/index', [
            'programs' => Program::published()->orderBy('sort_order')->get(['id', 'title', 'slug', 'excerpt']),
            'seo' => $this->seo('Our Programs'),
        ]);
    }

    public function show(string $slug, BlockHydrator $hydrator): Response
    {
        $program = Program::published()->where('slug', $slug)->with('ogMediaAsset.media')->firstOrFail();

        return $this->render('public/programs/show', [
            'program' => [
                ...$program->only('id', 'title', 'slug', 'excerpt', 'contact_name', 'contact_email', 'contact_phone', 'external_url'),
                'blocks' => $hydrator->hydrate($program->blocks ?? []),
            ],
            'seo' => $this->seo($program->seo_title ?: $program->title, $program->seo_description ?: $program->excerpt, $program->ogMediaAsset?->url),
        ]);
    }
}
