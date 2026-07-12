<?php

namespace Database\Seeders;

use App\Enums\PublishStatus;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\MediaAsset;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\TeamOgImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Imports content scraped from the legacy nativedadsnetwork.org into the CMS as
 * published records so the rebuilt public site has real content on day one.
 */
class LegacyContentSeeder extends Seeder
{
    private const TIMEZONE = 'America/Los_Angeles';

    private int $userId;

    public function run(): void
    {
        $this->userId = (User::first() ?? User::factory()->create())->id;

        $this->seedSettings();
        $this->seedTeam();
        $this->seedPartners();
        $this->seedPrograms();
        $this->seedEvents();
        $this->seedPosts();
        $this->seedGalleries();
        $this->seedPages();
        $this->seedMenus();
    }

    /** Resolve a seeded MediaAsset id by its original filename (see LegacyMediaSeeder). */
    private function asset(string $originalName): ?int
    {
        return MediaAsset::where('original_name', $originalName)->value('id');
    }

    private function seedSettings(): void
    {
        $values = [
            'site_name' => 'Native Dads Network',
            'tagline' => 'Little Efforts Make Big Changes',
            'contact_email' => 'info@nativedadsnetwork.org',
            'contact_phone' => '(916) 910-7663',
            'mailing_address' => '2151 River Plaza Dr Ste 200, Sacramento, CA 95833',
            'facebook_url' => 'https://www.facebook.com/NativeDadsNetwork',
            'youtube_url' => 'https://www.youtube.com/channel/UC51jvot9qU3dRVmxn54QGKA',
            'footer_text' => '© '.now()->year.' Native Dads Network. We are here to heal generational trauma and help lay the path forward.',
            'logo' => $this->asset('logo.png'),
        ];

        // Use model saves (not a query-builder update) so the json cast is
        // applied and the cached settings blob is busted.
        foreach ($values as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting !== null) {
                $setting->value = $value;
                $setting->save();
            }
        }
    }

    private function seedGalleries(): void
    {
        $this->gallery('Community', 'Photos from Native Dads Network gatherings, circles, and community events.', 'gallery', 'IMG_');
        $this->gallery('Woodland Mural Project', 'The 60-by-30-foot cultural mural completed by Native American students at Douglass Middle School.', 'mural', 'mural');
    }

    private function gallery(string $title, string $description, string $category, string $filenamePrefix): void
    {
        $gallery = Gallery::updateOrCreate(['slug' => Str::slug($title)], [
            'title' => $title, 'description' => $description,
            'status' => PublishStatus::Published, 'published_at' => now(),
            'created_by' => $this->userId, 'updated_by' => $this->userId,
        ]);

        $assets = MediaAsset::where('original_name', 'like', $filenamePrefix.'%')
            ->orderBy('original_name')
            ->pluck('id');

        $sync = [];
        foreach ($assets as $order => $id) {
            $sync[$id] = ['alt_text' => "{$title} photo ".($order + 1), 'sort_order' => $order];
        }

        $gallery->mediaAssets()->sync($sync);
    }

    private function seedTeam(): void
    {
        // [name, title, group, photo filename (in the media library), bio]
        $members = [
            ['Mike Duncan', 'Executive Director', 'staff', 'mike-duncan.jpg',
                "Mike Duncan is an enrolled member of Round Valley Indian Tribes. His tribal heritage is Maidu/Wailaki/Wintun and Western Band Shoshone. In 2012 Mike Duncan founded Native Dads Network and is currently the CEO of the 501c3 non-profit. Since 2009 Mike has facilitated the \"Fatherhood/Motherhood is Sacred\" curriculum with great success and has helped create a network of Fatherhood/Motherhood groups in Northern California.\n\nAlso in this time, he has worked in urban and rural Tribal communities conducting workshops discussing topics such as Fatherhood/Motherhood Is Sacred, Historical Trauma, Cultural Competency and Healthy Relationships. He has used these topics and personal stories to help participants look at barriers and to encourage traditional teaching as solutions."],
            ['Albert G Titman Sr.', 'Deputy Director', 'staff', 'albert-titman-sr.jpg',
                "Albert G. Titman, Nisenan/Miwok/Maidu/Pit River, CADC II. Deputy Director for the Native Dads Network and formerly Associate Director of Cultural Integration and Development at Sprenger Behavioral Medicine for the TeleWell Indian Health MAT project. He is a Registered Addiction Specialist through the Breining Institute of CA and a State Board CCAPP Certified Alcohol and Drug Abuse Counselor CADC II. He also provides alcohol/drug abuse assessments, diagnosis, and treatment to individuals, couples, families, and groups. He enjoys Miwok traditional ceremonial singing and dancing and cooking for his family.\n\nAlbert provides culturally sensitive services and is blessed with the opportunity to incorporate Native American wellness modalities in his work. He is currently a trainer for White Bison's Wellbriety Training Institute, and has over 18 years experience in implementing the Medicine Wheel & 12 Steps program in his community."],
            ['Joshua Mize', 'Program Coordinator', 'staff', 'joshua-mize.jpg',
                "Joshua Mize, an enrolled member of the Ho-Chunk Nation of Wisconsin and a tribal descendant of the Menominee, Osage, and Quapaw Nations, embodies a deep commitment to family, community, and cultural heritage. As a devoted father to three daughters and caregiver to his nephew, he actively engages in the Native Dads Network, drawing inspiration from the principles of Fatherhood is Sacred and White Bison.\n\nDriven by a passion for social change, Josh's journey through higher education includes UC Davis, American River College, Sacramento City College, and Folsom Lake College. Reflecting on his own experiences as a Native student navigating the K-12 system, Josh recognizes the pervasive invisibility and systemic barriers that hinder educational attainment. Despite these challenges, he persevered and earned a bachelor's degree in Native American Studies, with a Minor in Community Development.\n\nNow, Josh is dedicated to breaking down the entrenched barriers of systemic racism that afflict Sacramento communities. He seeks to foster collaborative initiatives aimed at transforming educational pathways from K-12 onward, empowering marginalized voices and creating a more equitable future for all."],
            ['Albert Titman Jr.', 'IMPACTT Program Coordinator', 'staff', 'albert-titman-jr.jpg',
                "Albert is a proud member of the Nisenan, Miwok, Maidu, and Madesi band of the Pit River Nation, raised in his ancestral homelands of Sacramento. Guided by his love for his people and the land, he honors his roles as a father, son, and brother while drawing strength from his ancestors' resilience.\n\nFor six years, Albert has worked on Native American Graves Protection and Repatriation Act (NAGPRA) efforts, representing local tribes to protect sacred sites and repatriate sacred objects and ancestral remains. He also supports youth programs through tribal nonprofits and initiatives like the Native Dads Network, Native Sister Circle, and Sacramento Native Health Clinic. Currently, he is the IMPACTT Youth Coordinator with the Native Dads Network, advancing cultural traditions and wellness.\n\nAlbert is pursuing a degree in Native American Studies and Political Science through the Native American Student Leadership Program at American River College. He is also the founder of Indigi Cuisine, a catering business celebrating traditional Native foods like salmon and deer."],
            ['Patricia Goodwin', 'Executive Assistant', 'staff', 'patricia-goodwin.jpg',
                "Patricia Goodwin is a remarkable individual whose multifaceted roles as a devoted mother of two, a dedicated student, a traditional dancer, and a valued community leader define her impactful presence. Recently graduating from Sacramento City College with an A.S. in nutrition, Patricia harbors aspirations of furthering her education in medicine, driven by her unwavering commitment to serving others.\n\nDeeply connected to her cultural roots, Patricia's passion lies in fostering wellness within tribal communities through the integration of traditional practices and cultural wisdom as proactive measures for prevention. Her extensive involvement as the Senior Program Specialist for the Native Dads Network underscores her dedication to uplifting and empowering Indigenous families.\n\nWith a heart devoted to service and a spirit grounded in tradition, Patricia embodies the essence of resilience, compassion, and strength."],
            ['Dewi (Sofia) Nainggolan', 'Administrative Assistant', 'staff', 'dewi-nainggolan.jpg',
                "Dewi Nainggolan is a dynamic individual whose journey has been shaped by her diverse background and multifaceted career path. Born in Indonesia, she moved to the United States at the age of nine, bringing with her a deep connection to her heritage and culture. Fluent in Bahasa Indonesia and deeply knowledgeable about her cultural roots, Dewi takes pride in her heritage and ensures that it remains an integral part of her life. As a dedicated mother to one daughter, she prioritizes passing down her cultural traditions to the next generation.\n\nDewi's educational journey took her to Pacific Union College in Angwin, Napa. She earned certifications as a Medical Assistant, Dental Assistant, and Accountant (AA Degree). After spending nine years in the medical field, Dewi decided to pivot towards business, and is currently pursuing a Bachelor's degree in Human Resources Management. Beyond her academic and professional pursuits, Dewi is an avid traveler and has a passion for music, particularly playing the piano and singing with an Indonesian Vocal Choir Group."],
            ['Love Duncan', 'IMPACTT Youth Intern', 'staff', 'love-duncan.png',
                "Love Duncan is proud to be Concow, Wailaki, Wintun from Round Valley Indian Reservation, as well as Western Band Shoshone from Nevada. In 2023, she made history as the first Native person to deliver a Land Acknowledgement at Woodland Unified, a significant milestone that reflects her deep commitment to honoring and sharing the history of her people. Love is passionate about preserving and promoting her cultural heritage through Traditional Dancing, Basket Making, and Gathering.\n\nShe has contributed to three murals, with two in Sacramento and one in Woodland, showcasing the beauty and strength of Native culture through visual expression. An accomplished speaker and leader, Love has MCed a major conference with over 200 attendees, and has been a panelist on numerous discussions, including one in Alaska, where she shared her insights on Native identity, cultural preservation, and community building."],
            ['Beau Medicine Crow', 'Board Chair', 'board', 'beau-medicine-crow.jpg',
                "Beau Medicine Crow is a member of the Mandan, Hidatsa, & Arikara Nations and is a child of the Water Buster, one of a few clans left with the Hidatsa. Beau's Indian name is Mah-eeshu-icagish, translated \"Lone Eagle.\" On his mother's side of the family, Beau is a descendant of the Washoe Tribe of Nevada & California; he belongs to a California band of Washoe, known as Hung A-lel-ti.\n\nMr. Medicine Crow has worked with various Indian tribes, state agencies, and grassroots organizations for the benefit of Indian peoples. He is a former tribal councilman and NCAI delegate from 2002 to 2006. He has worked with Native American children and families, the formerly incarcerated, and incarcerated youth. His passion is working with people in their recovery process and working with the unhoused population.\n\nBeau earned a Bachelor's Degree in Social Work, with a Minor in Native American Studies, from California State University Sacramento, and is currently in the MSW application process. He is a registered member of CCAPP, sits on an NASW Alcohol, Tobacco, & Other Drugs Committee, and is a member of NICWA. Beau will be celebrating 28 years of marriage to Rhonda Medicine Crow, with whom he raised 4 children."],
            ['Nikki Grant', 'Vice-Chair', 'board', 'nikki-grant.jpg',
                "I am Oglala Lakota from Pine Ridge, South Dakota, and a proud mom of five, born and raised in Sacramento, California. For the past three years, I've worked in Native community programs focused on building up families and reconnecting to culture. I currently serve as Chair of the Native American Parent Advisory Committee (NAPAC) for Folsom Cordova Unified School District and sit on the board for their Indian Education Program.\n\nI am dedicated to standing up for Native youth and families and making sure their voices are heard in schools, programs, and the community. I am all about healing, leadership, and lifting up my people through culture and connection."],
            ['Joshua Hoaglen Sr.', 'Secretary', 'board', 'joshua-hoaglen-sr.jpg',
                "Joshua Hoaglen Sr. I am Konkow, Wailaki, Nomalaki, and Mono. I am an enrolled member of the Round Valley Indian Tribes, a father of 4, and grandfather of 2. I am the Program Educator for Native American Education in the Elk Grove Unified School District. I am a football coach, a basketball coach, and an active community member who believes that it is every man's duty to do their part to make their community a better place to live for all."],
            ['Lisa Mckay', 'Treasurer', 'board', 'lisa-mckay.jpg',
                "Lisa McKay is a California Native woman of the Pomo, Wailaki and Wintun tribes, a mother of two and youngest girl of six children. Raised in the East Bay Area of California, her family has been an active part of the Native community, participating in racial justice and civil rights protests including the occupation of Alcatraz Island. After graduating from Haskell Indian College in Kansas, Lisa came home to the Bay Area to work for her Native community.\n\nShe worked in nonprofit Native American health care clinics in Oakland, San Francisco and Sacramento for a total of 27 years, providing administrative, finance, and HR services to the staff and community. Lisa is an artist and beader, and is currently learning to weave baskets. Lisa is now working for the Alameda County Public Defender's office in Oakland, California."],
            ['Wyatt Kelly', 'Member At Large', 'board', null,
                "Wyatt Kelly is a young Apache man, dedicated advocate, creative, and organizer deeply rooted in community health and well-being. With a Bachelor's degree in English and Native American Studies from the University of California, Riverside, and a Master's in Organizational Leadership from Arizona State University, Wyatt brings both academic insight and lived experience to his work.\n\nHis efforts span across urban and rural Indian Country, where he focuses on equity, healing, and Indigenous self-determination. Whether leading statewide public health initiatives, advising on policy, or contributing to research, Wyatt weaves together traditional knowledge and modern innovation to uplift Native communities. Guided by the principle of acting for the next seven generations, Wyatt is committed to transforming systems, centering Native voices, and helping build a future rooted in sovereignty, strength, and community well-being."],
        ];

        $og = app(TeamOgImageGenerator::class);

        foreach ($members as $i => [$name, $title, $group, $photo, $bio]) {
            $member = TeamMember::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'title' => $title, 'group' => $group, 'bio' => $bio,
                'photo_media_asset_id' => $photo !== null ? $this->asset($photo) : null,
                'show_email' => false, 'show_phone' => false, 'is_active' => true,
                'sort_order' => $i, 'created_by' => $this->userId, 'updated_by' => $this->userId,
            ]);

            // Model events are muted during seeding (WithoutModelEvents), so the
            // OG-image hook does not fire; generate it explicitly here.
            $path = $og->generate($member->load('photo.media'));
            if ($path !== null) {
                $member->og_image_path = $path;
                $member->saveQuietly();
            }
        }
    }

    private function seedPartners(): void
    {
        // [name, website, logo filename in the media library]
        $partners = [
            ['7th Generation Vitality Grant', 'https://www.7genfund.org', '7th Generation Fund.png'],
            ['Elevate Youth California', 'https://www.elevateyouthca.org', 'Elevate Youth California.png'],
            ['Common Counsel Foundation', 'https://www.commoncounsel.org', 'commonCounsel.png'],
            ['Native Voices Rising', 'https://nativevoicesrising.org', 'Native Voices Rising.png'],
            ['The California Endowment', 'https://www.calendow.org', 'CE.jpg'],
        ];

        foreach ($partners as $i => [$name, $website, $logo]) {
            Partner::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'website_url' => $website,
                'logo_media_asset_id' => $this->asset($logo),
                'sort_order' => $i, 'is_active' => true,
                'created_by' => $this->userId, 'updated_by' => $this->userId,
            ]);
        }
    }

    private function seedPrograms(): void
    {
        // [title, excerpt, array<block>]
        $programs = [
            ['Wellness Department', 'Trauma-informed, culturally-centered healing and support services.', [
                $this->richTextParagraphs('The Wellness Department offers culturally rich support that inspires, motivates, and strengthens families and their communities.'),
                $this->heading('What We Offer'),
                $this->richTextParagraphs(
                    "Individual & Family Therapy with a trauma-informed licensed mental health professional, and culturally knowledgeable substance use disorder counseling with a certified substance use professional.\n\n".
                    "Motherhood & Fatherhood is Sacred — culturally centered parenting courses, Wellbriety groups, and talking circles. A graduation ceremony convenes upon completion of the 12-week courses.\n\n".
                    'Red Road Talking Circle — culturally centered gatherings for healing and connection.'
                ),
                $this->heading('How to Register'),
                $this->richTextParagraphs(
                    "Call: 916-910-7663 · Email: info@nativedadsnetwork.org\n\n".
                    'To become a participant in the NDN Wellness Program, contact our office. Eligibility requires tribal enrollment/membership or documentation showing descendancy from an enrolled tribal member (descendants can link back two generations), an intake and screening assessment, and a signed Program Participation Agreement.'
                ),
            ]],
            ['Youth Programming', 'Empowering the next generation through cultural identity and leadership development.', [
                $this->richTextParagraphs(
                    "IMPACTT — Indigenous Mentors Protecting Ancestral Cultural Teachings Team — creates a safe place for tribal youth, amplifies their voices, and nurtures the next generation of leaders by grounding them in cultural and spiritual traditions.\n\n".
                    'Rooted in culture, spirituality, and ceremonial practices, IMPACTT functions as a youth prevention program that nurtures future leaders through workshops, listening sessions, and traditional teachings.'
                ),
                $this->heading('Featured Mural Projects'),
                $this->richTextParagraphs(
                    'IMPACTT has completed 3 mural projects in Yolo and Sacramento County. Each project included 5 weeks of workshops, where youth ages 14-18 learned, designed, and painted murals representing original Patwin, Miwok and Nisenan culture and lifeways — at Douglass Middle School (Woodland USD), Grant High School (Twin Rivers USD), and Miwok Middle School (Sacramento City USD).'
                ),
                $this->heading('Next Gen Youth Conference'),
                $this->richTextParagraphs(
                    "An annual event in Sacramento, planned by and for Native youth, providing the tools and inspiration to become leaders in their communities. A recent conference featured keynote speaker Alaqua Cox (Marvel's Echo star), and workshops on healing from ACEs, stress management, and herbalism as cultural healing."
                ),
            ]],
            ['Annual Conferences', 'Transformative gatherings fostering healing, empowerment, and cultural connection.', [
                $this->heading('Boys with Braids Conference'),
                $this->richTextParagraphs(
                    'A culturally significant event empowering Native American youth by celebrating the importance of long hair in Indigenous traditions while addressing racial harassment. Featured speakers have included Dallas Goldtooth, Kauchani Bratt, and Michael Linklater, founder of the Boys with Braids movement.'
                ),
                $this->heading('Wellness Tour'),
                $this->richTextParagraphs(
                    'A culturally grounded initiative launched in response to the substance use crisis during COVID-19. From March 2021 to June 2022, the tour reached 11 American Indian and Alaska Native tribal communities across California, offering Naloxone (Narcan) training, cultural healing through song and prayer, and traditional talking circles.'
                ),
                $this->heading('Healing Together Conference'),
                $this->richTextParagraphs(
                    'Held at Sky Ute Casino Resort in Ignacio, Colorado, in collaboration with White Bison, Native Wellness Institute, and the Native American Fatherhood & Families Association. The conference fosters cultural healing, addresses intergenerational trauma, and promotes wellness and leadership among Native youth and families.'
                ),
            ]],
            ['IMPACTT', 'Indigenous Mentors Protecting Ancestral Cultural Tribal Traditions.', [
                $this->richTextParagraphs(
                    "IMPACTT's purpose is to arm Native youth with the traditional knowledge and educational skills to become future leaders. We aim to bolster our communities by employing cultural education as a preventative measure, while empowering Native youth to advocate for themselves through youth organizing and community participation.\n\n".
                    'IMPACTT (Indigenous Mentors Protecting Ancestral Cultural Teachings Team) is a Native American Youth Leadership Program initiated by Native Dads Network. Through workshops, listening sessions, and traditional teachings, we nurture the growth of future leaders, empowering them to effect lasting change.'
                ),
                $this->heading('Featured Work'),
                $this->richTextParagraphs(
                    "Woodland Unified School District: Native Resource Center, Native resource library, Mural Project, and Native Graduation.\n\n".
                    "Twin Rivers Unified School District: First Annual Pow Wow/Big Time, Native Graduation/Promotion Ceremony, Cultural Workshops, and Mural Project.\n\n".
                    "Sacramento City Unified School District: a mural project signifying the historical name change from Sutter Middle School to Miwok Middle School.\n\n".
                    'Folsom Cordova Unified School District: with support from Native Dads Network, the district established an official Title VI Indian Education program in 2024.'
                ),
            ]],
        ];

        foreach ($programs as $i => [$title, $excerpt, $blocks]) {
            Program::updateOrCreate(['slug' => Str::slug($title)], [
                'title' => $title, 'excerpt' => $excerpt,
                'blocks' => $blocks,
                'status' => PublishStatus::Published, 'published_at' => now(),
                'sort_order' => $i, 'created_by' => $this->userId, 'updated_by' => $this->userId,
            ]);
        }
    }

    private function seedEvents(): void
    {
        // Wall-clock times are local to the event's own zone; `event()` converts
        // them to the UTC instant that gets stored.
        $tuesday = Carbon::now(self::TIMEZONE)->next(Carbon::TUESDAY);
        $fortnight = Carbon::now(self::TIMEZONE)->addWeeks(2);

        $this->event('Red Road To Wellbriety', 'A weekly Wellbriety circle walking the Red Road to recovery and wellbeing. Every Tuesday.',
            $tuesday->copy()->setTime(17, 30), $tuesday->copy()->setTime(19, 30), 'Native Dads Network');

        $this->event('Wake Up With Wellness', 'Start your Tuesday with wellness. Join us weekly for a morning of connection and care.',
            $tuesday->copy()->setTime(6, 30), $tuesday->copy()->setTime(7, 30), null, true);

        $this->event('Native Dads Network Parenting Circle', 'A parenting circle for fathers and families, meeting regularly to share, learn, and support one another.',
            $fortnight->copy()->setTime(17, 30), $fortnight->copy()->setTime(19, 30), 'Native Dads Network');
    }

    private function event(string $title, string $desc, Carbon $start, Carbon $end, ?string $location, bool $virtual = false): void
    {
        Event::updateOrCreate(['slug' => Str::slug($title)], [
            'title' => $title,
            'description' => [$this->richText($desc)],
            'starts_at' => $start->copy()->utc(), 'ends_at' => $end->copy()->utc(), 'all_day' => false,
            'timezone' => self::TIMEZONE,
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
                $this->richTextParagraphs('Native Dads Network is a 501(c)(3) community based group offering support to fathers, mothers, and their families. Our approach is a culturally rich model that inspires, motivates and strengthens families and their communities. Native Dads Network is for the people. We are here to heal generational trauma and help lay the path forward.'),
                $this->heading('Our Purpose'),
                $this->richTextParagraphs('Native Dads Network\'s purpose is to protect and restore the indigenous family structure through culture, education, and inter-generational healing.'),
                $this->heading('Our Vision'),
                $this->richTextParagraphs('Through comprehensive, well-coordinated, and culturally sensitive services, our vision is to address, reduce, and ultimately eliminate the cycle of addiction, domestic violence, sexual assault, child abuse, incarceration, suicide, poverty, homelessness, and mental illness — among other harmful behaviors and societal stressors affecting the well-being of our Native communities.'),
                $this->heading('Our Background'),
                $this->richTextParagraphs("The birth of NDN began as part of Mr. Mike Duncan's journey, a Con-Cow/Wailaki/Wintun California Native and NDN's Executive Director. Prior to developing the vision of NDN in 2010, Mr. Duncan experienced many of the challenges faced by Native communities when confronted with a lack of culturally and linguistically sensitive services. Since 2010, NDN has expanded its wings as conferences and services have been organized and delivered to Native communities living in Northern California and nearby areas.\n\nAfter program evaluation in 2017, NDN's Executive Director, Board of Directors, and Volunteers developed a strategic plan — a roadmap for accomplishing specified goals, including allocation of current resources, organizational growth, and approaches for continuing to reach our mission and vision."),
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

    /**
     * A single rich_text block holding one paragraph per blank-line-separated
     * chunk of $text — used for the longer legacy program/about copy.
     *
     * @return array{id: string, type: string, data: array<string, mixed>}
     */
    private function richTextParagraphs(string $text): array
    {
        $paragraphs = [];
        foreach (preg_split('/\n\n+/', trim($text)) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $paragraphs[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $chunk]]];
        }

        return [
            'id' => 'b'.Str::random(12),
            'type' => 'rich_text',
            'data' => ['content' => ['type' => 'doc', 'content' => $paragraphs]],
        ];
    }

    /**
     * A heading block (rich_text with a single h2 node).
     *
     * @return array{id: string, type: string, data: array<string, mixed>}
     */
    private function heading(string $text): array
    {
        return [
            'id' => 'b'.Str::random(12),
            'type' => 'rich_text',
            'data' => ['content' => ['type' => 'doc', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $text]]]]]],
        ];
    }
}
