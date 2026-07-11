<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Imports content scraped from the legacy nativedadsnetwork.org into the CMS as
 * published records so the rebuilt public site has real content on day one.
 */
class LegacyContentSeeder extends Seeder
{
    private int $userId;

    public function run(): void
    {
        $this->userId = (User::first() ?? User::factory()->create())->id;

        $this->seedSettings();
        $this->seedTeam();
        $this->seedPrograms();
        $this->seedEvents();
        $this->seedPosts();
        $this->seedPages();
        $this->seedMenus();
    }

    private function seedSettings(): void
    {
        $values = [
            'site_name' => 'Native Dads Network',
            'tagline' => 'Healing generational trauma and laying the path forward.',
            'contact_email' => 'info@nativedadsnetwork.org',
            'contact_phone' => '(916) 910-7663',
            'mailing_address' => '2151 River Plaza Dr Ste 200, Sacramento, CA 95833',
            'facebook_url' => 'https://www.facebook.com/NativeDadsNetwork',
            'footer_text' => '© '.now()->year.' Native Dads Network',
        ];

        foreach ($values as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }
    }

    private function seedTeam(): void
    {
        $members = [
            ['Mike Duncan', 'Executive Director', 'Founder and Executive Director of the Native Dads Network, leading its mission to support fathers, families, and Native youth across the community.'],
            ['Albert Titman', 'Deputy Director', 'Deputy Director supporting the organization\'s programs, partnerships, and day-to-day operations.'],
            ['Patricia Titman', 'IMPACTT Program Coordinator', 'Coordinates the IMPACTT program, organizing youth and family programming and community events.'],
            ['Joshua Hoaglen Sr.', 'Board Chair', 'Chair of the Board of Directors, providing governance and strategic direction for the organization.'],
        ];

        foreach ($members as $i => [$name, $title, $bio]) {
            TeamMember::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'title' => $title, 'bio' => $bio,
                'show_email' => false, 'show_phone' => false, 'is_active' => true,
                'sort_order' => $i, 'created_by' => $this->userId, 'updated_by' => $this->userId,
            ]);
        }
    }

    private function seedPrograms(): void
    {
        $programs = [
            ['Wellness Department', 'Culturally grounded wellness programming supporting healing and recovery for fathers, families, and community members.',
                'The Wellness Department offers culturally rich support that inspires, motivates, and strengthens families and their communities. Through circles, gatherings, and recovery-focused programming, we help community members heal generational trauma and build wellbeing.'],
            ['Youth Programming', 'Programs and cultural workshops that support cultural identity and community engagement for Native youth.',
                'Our youth programming provides a safe space that supports cultural identity and community engagement for Native youth. From cultural workshops to community mural projects, we create opportunities for young people to connect with culture, tradition, and one another.'],
            ['Conferences', 'Convenings and conferences that bring together fathers, families, and partners to share knowledge and strengthen community.',
                'We host and support conferences that bring fathers, families, and partners together to share knowledge, celebrate culture, and strengthen the network of support around Native families.'],
        ];

        foreach ($programs as $i => [$title, $excerpt, $body]) {
            Program::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title, 'excerpt' => $excerpt,
                'blocks' => [$this->richText($body)],
                'status' => PublishStatus::Published, 'published_at' => now(),
                'sort_order' => $i, 'created_by' => $this->userId, 'updated_by' => $this->userId,
            ]);
        }
    }

    private function seedEvents(): void
    {
        $tuesday = Carbon::now()->next(Carbon::TUESDAY);

        $this->event('Red Road To Wellbriety', 'A weekly Wellbriety circle walking the Red Road to recovery and wellbeing. Every Tuesday.',
            $tuesday->copy()->setTime(17, 30), $tuesday->copy()->setTime(19, 30), 'Native Dads Network');

        $this->event('Wake Up With Wellness', 'Start your Tuesday with wellness. Join us weekly for a morning of connection and care.',
            $tuesday->copy()->setTime(6, 30), $tuesday->copy()->setTime(7, 30), null, true);

        $this->event('Native Dads Network Parenting Circle', 'A parenting circle for fathers and families, meeting regularly to share, learn, and support one another.',
            Carbon::now()->addWeeks(2)->setTime(17, 30), Carbon::now()->addWeeks(2)->setTime(19, 30), 'Native Dads Network');
    }

    private function event(string $title, string $desc, Carbon $start, Carbon $end, ?string $location, bool $virtual = false): void
    {
        Event::updateOrCreate(['slug' => Str::slug($title)], [
            'title' => $title,
            'description' => [$this->richText($desc)],
            'starts_at' => $start, 'ends_at' => $end, 'all_day' => false,
            'timezone' => 'America/Los_Angeles',
            'location_name' => $location, 'is_virtual' => $virtual,
            'registration_url' => 'mailto:info@nativedadsnetwork.org',
            'status' => PublishStatus::Published, 'published_at' => now(),
            'created_by' => $this->userId, 'updated_by' => $this->userId,
        ]);
    }

    private function seedPosts(): void
    {
        Post::updateOrCreate(['slug' => 'woodland-mural-project'], [
            'title' => 'Woodland Mural Project',
            'excerpt' => 'Native American students completed a 60-by-30-foot cultural mural at Douglass Middle School, organized by the Native Dads Network.',
            'blocks' => [
                $this->richText('Local seventh through 12th grade Native American students in and around the Woodland Joint Unified School District completed Woodland\'s newest artistic addition, a mural on the south side of the Douglass Middle School gymnasium.'),
                $this->richText('The 60 by 30-foot cultural mural, which took four days to complete, was unveiled during a Friday morning celebration. The mural is the culmination of a series of cultural workshops for Native American students organized by the Native Dads Network.'),
                $this->richText('"This is one of the proudest moments of my life based on the teamwork, vision, goal setting and accomplishing of it," said Michael Duncan, Founder and Executive Director of the Native Dads Network. In the heart of the mural, a young Native dancer represents future generations, beside a red dress honoring Missing and Murdered Indigenous Women.'),
            ],
            'is_featured' => true, 'status' => PublishStatus::Published, 'published_at' => now()->subMonths(1),
            'author_id' => $this->userId, 'created_by' => $this->userId, 'updated_by' => $this->userId,
        ]);

        Post::updateOrCreate(['slug' => 'wjusd-board-honors-native-heritage'], [
            'title' => 'WJUSD Board Honors Native Heritage With New Name for Student Resource Center',
            'excerpt' => 'The Native Student Resource Center at Douglass Middle School is now known as "Thicha Wole," a Wintun phrase meaning "Learning Room."',
            'blocks' => [
                $this->richText('A space created to support and uplift Native American students now has a name that reflects its purpose and the deep cultural roots of the region. The Native Student Resource Center will now be known as "Thicha Wole," a Wintun phrase meaning "Learning Room."'),
                $this->richText('The name was proposed by the District\'s American Indian Parent Committee in partnership with Native Dads Network Executive Director Michael Duncan and other local Native families. The name honors the Wintun people and the ancestral homeland on which the school resides.'),
            ],
            'status' => PublishStatus::Published, 'published_at' => now()->subWeeks(2),
            'author_id' => $this->userId, 'created_by' => $this->userId, 'updated_by' => $this->userId,
        ]);
    }

    private function seedPages(): void
    {
        Page::updateOrCreate(['parent_key' => 0, 'slug' => 'about', 'locale' => 'en'], [
            'title' => 'About Us', 'parent_id' => null,
            'blocks' => [
                $this->richText('Native Dads Network is a 501(c)(3) community based group offering support to fathers, mothers, and their families. Our approach is a culturally rich model that inspires, motivates and strengthens families and their communities.'),
                $this->richText('Native Dads Network is for the people. We are here to heal generational trauma and help lay the path forward.'),
            ],
            'status' => PublishStatus::Published, 'published_at' => now(),
            'locale' => 'en', 'is_locked' => false, 'sort_order' => 0,
            'created_by' => $this->userId, 'updated_by' => $this->userId,
        ]);
    }

    private function seedMenus(): void
    {
        $header = Menu::where('slot', 'header')->first();
        if ($header !== null) {
            $header->items()->delete();
            $links = [
                ['About Us', '/about'],
                ['Our Programs', '/programs'],
                ['Events', '/events'],
                ['News', '/news'],
                ['Gallery', '/gallery'],
                ['Contact Us', '/contact'],
            ];
            foreach ($links as $order => [$label, $url]) {
                $header->items()->create(['label' => $label, 'custom_url' => $url, 'opens_new_tab' => false, 'sort_order' => $order]);
            }
        }

        $footer = Menu::where('slot', 'footer')->first();
        if ($footer !== null) {
            $footer->items()->delete();
            foreach ([['Programs', '/programs'], ['Events', '/events'], ['Gallery', '/gallery'], ['Contact Us', '/contact']] as $order => [$label, $url]) {
                $footer->items()->create(['label' => $label, 'custom_url' => $url, 'opens_new_tab' => false, 'sort_order' => $order]);
            }
        }
    }

    /**
     * @return array{id: string, type: string, data: array<string, mixed>}
     */
    private function richText(string $text): array
    {
        return [
            'id' => 'b'.Str::random(12),
            'type' => 'rich_text',
            'data' => ['content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]]],
        ];
    }
}
