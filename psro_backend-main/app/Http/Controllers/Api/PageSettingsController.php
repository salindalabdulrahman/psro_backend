<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\FooterLink;
use App\Models\HeaderSetting;
use App\Models\Headline;
use App\Models\Mandate;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PageSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        $headline = Headline::current();
        $mandate = Mandate::current();
        $header = HeaderSetting::current();
        $activities = Activity::ordered()->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'category' => $a->category,
            'description' => $a->description,
            'content' => $a->content,
            'image' => $a->image,
            'link' => $a->link,
            'date' => $a->date?->format('Y-m-d'),
            'is_published' => $a->is_published,
        ])->values();

        $footerMinistries = $this->loadFooterGroup('ministry');
        $footerAgencies = $this->loadFooterGroup('agency');

        return response()->json([
            'headline' => $headline->headline,
            'sub_headline' => $headline->sub_headline,
            'hero_image_url' => $headline->hero_image_url,
            'hero_slider_images' => $this->getHeroSliderImages($headline),
            'mandate' => [
                'headline' => $mandate->headline,
                'subheadline' => $mandate->subheadline,
                'director_quote' => $mandate->director_quote,
                'director_name' => $mandate->director_name,
                'director_title' => $mandate->director_title,
                'director_image_url' => $mandate->director_image_url ?? '',
            ],
            'activities' => $activities,
            'footer_ministries' => $footerMinistries,
            'footer_agencies' => $footerAgencies,
            'draft_headline' => $headline->headline,
            'draft_sub_headline' => $headline->sub_headline,
            'draft_hero_image_url' => $headline->hero_image_url,
            'draft_hero_slider_images' => $this->getHeroSliderImages($headline),
            'draft_mandate' => [
                'headline' => $mandate->headline,
                'subheadline' => $mandate->subheadline,
                'director_quote' => $mandate->director_quote,
                'director_name' => $mandate->director_name,
                'director_title' => $mandate->director_title,
                'director_image_url' => $mandate->director_image_url ?? '',
            ],
            'draft_activities' => $activities,
            'header' => [
                'logo_url' => $header->logo_url,
                'org_line1' => $header->org_line1,
                'org_line2' => $header->org_line2,
                'office_title' => $header->office_title,
                'nav_links' => $header->nav_links_json ?? [],
                'social_links' => $header->social_links_json ?? [],
            ],
            'hero_feature' => $this->getHeroFeature(),
            'problem_section' => $this->getLandingSection('problem_section', [
                'headline' => 'The challenges we address',
                'pain_points' => [
                    'Fragmented coordination across peace, security, and development actors in the region.',
                    'Communities facing conflict and displacement without adequate support systems.',
                    'Limited evidence-based policy frameworks for sustainable peacebuilding.',
                ],
            ]),
            'services_section' => $this->getLandingSection('services_section', [
                'headline' => 'What we do',
                'subheadline' => 'Our services address the challenges of peacebuilding across the Bangsamoro region.',
                'services' => [
                    ['title' => 'Peace & Security Coordination', 'description' => 'Serving as the primary facilitator for coherence across the BARMM system.', 'icon' => 'shield'],
                    ['title' => 'Conflict Transformation', 'description' => 'Driving impactful system-wide action through strategic advice and policy guidance.', 'icon' => 'hand'],
                    ['title' => 'Evidence-Informed Policy Support', 'description' => 'Supporting data-driven program design and adjustment for lasting impact.', 'icon' => 'chart'],
                    ['title' => 'Community Engagement', 'description' => 'Centering the voices and agency of affected communities in peacebuilding.', 'icon' => 'group'],
                ],
            ]),
            'testimonials_section' => $this->getLandingSection('testimonials_section', [
                'headline' => 'What partners say',
                'subheadline' => 'Stakeholders and partners share their experience working with PSRO-BARMM.',
                'testimonials' => [
                    ['quote' => 'PSRO has been instrumental in bringing together different actors for a coordinated approach to peace and security.', 'name' => 'Partner Representative', 'role' => 'Local Government Unit', 'image_url' => '/images/lgu.png'],
                    ['quote' => 'The evidence-informed approach helps us direct resources where they are most needed.', 'name' => 'Development Partner', 'role' => 'International NGO', 'image_url' => '/images/360_F_1091183481_lrB77TJUEMGmIpGZyYizS864CzsNf37J.jpg'],
                    ['quote' => 'Inclusive dialogue with communities and clearer follow-through across agencies have strengthened trust in joint peace and development efforts.', 'name' => 'Civil Society Representative', 'role' => 'People\'s Organization', 'image_url' => '/images/community-network-and-social-logo-design-vector%20(1).jpg'],
                ],
            ]),
            'about_us_section' => $this->getLandingSection('about_us_section', [
                'headline' => 'About PSRO',
                'short_text' => 'The Peace, Security & Reconciliation Office serves as the primary facilitator for coherence across the BARMM system, working horizontally across ministries and vertically from regional to community level.',
                'team_photos' => [],
            ]),
            'faq_section' => $this->getLandingSection('faq_section', [
                'headline' => 'Frequently Asked Questions',
                'subheadline' => 'Common questions about PSRO-BARMM and our work.',
                'items' => [
                    ['question' => 'What is PSRO-BARMM?', 'answer' => 'The Peace, Security & Reconciliation Office (PSRO) is a BARMM government office that serves as the primary facilitator for coherence across the peace, security, and development system in the Bangsamoro region.'],
                    ['question' => 'How can I partner with PSRO?', 'answer' => 'You can reach out through our contact form or official channels. We work with local government units, civil society, and development partners on peacebuilding initiatives.'],
                    ['question' => 'Where does PSRO operate?', 'answer' => 'PSRO operates across the Bangsamoro Autonomous Region in Muslim Mindanao, working at regional, provincial, and community levels.'],
                ],
            ]),
            'final_cta_section' => $this->getLandingSection('final_cta_section', [
                'headline' => 'Ready to partner with us?',
                'subheadline' => 'Get in touch to learn how we can work together for lasting peace in the Bangsamoro region.',
                'button_text' => 'Contact Us',
            ]),
            'header_cta' => $this->getLandingSection('header_cta', [
                'cta_text' => 'Contact Us',
                'show_cta' => true,
            ]),
            'impact_stats' => $this->getImpactStats(),
            'marquee_items' => $this->getMarqueeItems(),
            'section_visibility' => $this->getSectionVisibility(),
        ]);
    }

    private function getLandingSection(string $key, array $default): array
    {
        $data = SiteSetting::getValue($key, null);
        if (!$data || !is_array($data)) {
            return $default;
        }
        return array_merge($default, $data);
    }

    private function getImpactStats(): array
    {
        $data = SiteSetting::getValue('impact_stats', null);
        if (!$data || !is_array($data)) {
            return [
                ['value' => 100, 'suffix' => '+', 'label' => 'Communities Reached'],
                ['value' => 50, 'suffix' => '+', 'label' => 'Programs Delivered'],
                ['value' => 5, 'suffix' => '', 'label' => 'Years of Service'],
            ];
        }
        return array_values(array_filter($data, fn ($s) => is_array($s) && isset($s['value'])));
    }

    private function getMarqueeItems(): array
    {
        $data = SiteSetting::getValue('marquee_items', null);
        if (!$data || !is_array($data)) {
            return [
                'Evidence-informed policies',
                'People-centered peacebuilding',
                'Community-led initiatives',
                'Sustainable reconciliation',
                'Regional coordination',
            ];
        }
        return array_values(array_filter(array_map('trim', $data)));
    }

    private function getSectionVisibility(): array
    {
        $data = SiteSetting::getValue('section_visibility', null);
        if (!$data || !is_array($data)) {
            return [
                'mandate' => true,
                'activities' => true,
                'services' => true,
                'problem' => true,
                'testimonials' => true,
                'about' => true,
                'faq' => true,
                'contact' => true,
                'final_cta' => true,
                'impact_stats' => true,
            ];
        }
        return array_merge([
            'mandate' => true,
            'activities' => true,
            'services' => true,
            'problem' => true,
            'testimonials' => true,
            'about' => true,
            'faq' => true,
            'contact' => true,
            'final_cta' => true,
            'impact_stats' => true,
        ], $data);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'headline' => ['nullable', 'string', 'max:500'],
            'sub_headline' => ['nullable', 'string', 'max:1000'],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'hero_slider_images_json' => ['nullable', 'json'],
            'director_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'mandate_json' => ['nullable', 'json'],
            'activities_json' => ['nullable', 'json'],
            'footer_ministries_json' => ['nullable', 'json'],
            'footer_agencies_json' => ['nullable', 'json'],
            'header_json' => ['nullable', 'json'],
            'hero_feature_json' => ['nullable', 'json'],
            'problem_section_json' => ['nullable', 'json'],
            'services_section_json' => ['nullable', 'json'],
            'testimonials_section_json' => ['nullable', 'json'],
            'about_us_section_json' => ['nullable', 'json'],
            'faq_section_json' => ['nullable', 'json'],
            'final_cta_section_json' => ['nullable', 'json'],
            'header_cta_json' => ['nullable', 'json'],
            'impact_stats_json' => ['nullable', 'json'],
            'marquee_items_json' => ['nullable', 'json'],
            'section_visibility_json' => ['nullable', 'json'],
            'mode' => ['nullable', 'string', 'in:draft,publish'],
        ]);

        // ── Headline table ──
        $headline = Headline::current();

        if ($request->has('headline')) {
            $headline->headline = $request->input('headline');
        }
        if ($request->has('sub_headline')) {
            $headline->sub_headline = $request->input('sub_headline');
        }
        if ($request->hasFile('hero_image')) {
            if ($headline->hero_image_url) {
                $this->deleteStorageFile($headline->hero_image_url);
            }
            $path = $request->file('hero_image')->store('hero-images', 'public');
            $headline->hero_image_url = '/storage/' . $path;
        }
        $headline->save();

        // ── Mandate table ──
        $mandate = Mandate::current();
        if ($request->filled('mandate_json')) {
            $mandateData = json_decode((string) $request->input('mandate_json'), true);
            if (is_array($mandateData)) {
                $update = [
                    'headline' => trim((string) ($mandateData['headline'] ?? $mandate->headline)),
                    'subheadline' => trim((string) ($mandateData['subheadline'] ?? $mandate->subheadline)),
                    'director_quote' => trim((string) ($mandateData['director_quote'] ?? $mandate->director_quote)),
                    'director_name' => trim((string) ($mandateData['director_name'] ?? $mandate->director_name)),
                    'director_title' => trim((string) ($mandateData['director_title'] ?? $mandate->director_title)),
                ];
                if (array_key_exists('director_image_url', $mandateData) && $mandateData['director_image_url'] === '') {
                    $update['director_image_url'] = null;
                }
                $mandate->update($update);
            }
        }
        if ($request->hasFile('director_image')) {
            if ($mandate->director_image_url) {
                $this->deleteStorageFile($mandate->director_image_url);
            }
            $path = $request->file('director_image')->store('director-images', 'public');
            $mandate->director_image_url = '/storage/' . $path;
            $mandate->save();
        }

        // ── Activities table ──
        if ($request->filled('activities_json')) {
            $activitiesInput = json_decode((string) $request->input('activities_json'), true);
            if (is_array($activitiesInput)) {
                $this->syncActivities($activitiesInput);
            }
        }

        // ── Footer links ──
        if ($request->filled('footer_ministries_json')) {
            $items = json_decode((string) $request->input('footer_ministries_json'), true);
            if (is_array($items)) {
                $this->syncFooterLinks('ministry', $items);
            }
        }
        if ($request->filled('footer_agencies_json')) {
            $items = json_decode((string) $request->input('footer_agencies_json'), true);
            if (is_array($items)) {
                $this->syncFooterLinks('agency', $items);
            }
        }

        // ── Hero feature modal (site_settings) ──
        if ($request->filled('hero_feature_json')) {
            $heroFeature = json_decode((string) $request->input('hero_feature_json'), true);
            if (is_array($heroFeature)) {
                SiteSetting::setValue('hero_feature', $heroFeature, 'json');
            }
        }

        // ── Landing page sections (site_settings) ──
        foreach (['problem_section', 'services_section', 'testimonials_section', 'about_us_section', 'faq_section', 'final_cta_section', 'header_cta'] as $key) {
            $jsonKey = $key . '_json';
            if ($request->filled($jsonKey)) {
                $data = json_decode((string) $request->input($jsonKey), true);
                if (is_array($data)) {
                    SiteSetting::setValue($key, $data, 'json');
                }
            }
        }

        if ($request->filled('impact_stats_json')) {
            $data = json_decode((string) $request->input('impact_stats_json'), true);
            if (is_array($data)) {
                SiteSetting::setValue('impact_stats', array_values(array_filter($data, fn ($s) => is_array($s) && isset($s['value']))), 'json');
            }
        }
        if ($request->filled('marquee_items_json')) {
            $data = json_decode((string) $request->input('marquee_items_json'), true);
            if (is_array($data)) {
                $items = [];
                foreach ($data as $item) {
                    $s = is_string($item) ? trim($item) : '';
                    if ($s !== '') $items[] = $s;
                }
                SiteSetting::setValue('marquee_items', $items, 'json');
            }
        }
        if ($request->filled('section_visibility_json')) {
            $data = json_decode((string) $request->input('section_visibility_json'), true);
            if (is_array($data)) {
                SiteSetting::setValue('section_visibility', $data, 'json');
            }
        }
        if ($request->filled('hero_slider_images_json')) {
            $images = json_decode((string) $request->input('hero_slider_images_json'), true);
            if (is_array($images)) {
                $urls = [];
                foreach ($images as $item) {
                    $url = is_string($item) ? trim($item) : (is_array($item) && isset($item['url']) ? trim((string) $item['url']) : '');
                    if ($url !== '') {
                        $urls[] = $url;
                    }
                }
                SiteSetting::setValue('hero_slider_images', $urls, 'json');
            }
        }

        // ── Header settings ──
        if ($request->filled('header_json')) {
            $headerData = json_decode((string) $request->input('header_json'), true);
            if (is_array($headerData)) {
                $header = HeaderSetting::current();
                $newLogoUrl = mb_substr(trim((string) ($headerData['logo_url'] ?? $header->logo_url)), 0, 512);
                if ($header->logo_url !== $newLogoUrl && $header->logo_url && str_contains($header->logo_url, '/storage/')) {
                    $this->deleteStorageFile($header->logo_url);
                }
                $header->update([
                    'org_line1' => mb_substr(trim((string) ($headerData['org_line1'] ?? $header->org_line1)), 0, 255),
                    'org_line2' => mb_substr(trim((string) ($headerData['org_line2'] ?? $header->org_line2)), 0, 255),
                    'office_title' => mb_substr(trim((string) ($headerData['office_title'] ?? $header->office_title)), 0, 255),
                    'logo_url' => $newLogoUrl,
                    'nav_links_json' => $headerData['nav_links'] ?? $header->nav_links_json,
                    'social_links_json' => $headerData['social_links'] ?? $header->social_links_json,
                ]);
            }
        }

        // Re-fetch for response
        $headline->refresh();
        $mandate = Mandate::current();
        $activities = Activity::ordered()->get()->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'category' => $a->category,
            'description' => $a->description,
            'content' => $a->content,
            'image' => $a->image,
            'link' => $a->link,
            'date' => $a->date?->format('Y-m-d'),
            'is_published' => $a->is_published,
        ])->values();

        $footerMinistries = $this->loadFooterGroup('ministry');
        $footerAgencies = $this->loadFooterGroup('agency');

        return response()->json([
            'headline' => $headline->headline,
            'sub_headline' => $headline->sub_headline,
            'hero_image_url' => $headline->hero_image_url,
            'hero_slider_images' => $this->getHeroSliderImages($headline),
            'mandate' => [
                'headline' => $mandate->headline,
                'subheadline' => $mandate->subheadline,
                'director_quote' => $mandate->director_quote,
                'director_name' => $mandate->director_name,
                'director_title' => $mandate->director_title,
                'director_image_url' => $mandate->director_image_url ?? '',
            ],
            'activities' => $activities,
            'footer_ministries' => $footerMinistries,
            'footer_agencies' => $footerAgencies,
            'draft_headline' => $headline->headline,
            'draft_sub_headline' => $headline->sub_headline,
            'draft_hero_image_url' => $headline->hero_image_url,
            'draft_hero_slider_images' => $this->getHeroSliderImages($headline),
            'draft_mandate' => [
                'headline' => $mandate->headline,
                'subheadline' => $mandate->subheadline,
                'director_quote' => $mandate->director_quote,
                'director_name' => $mandate->director_name,
                'director_title' => $mandate->director_title,
                'director_image_url' => $mandate->director_image_url ?? '',
            ],
            'draft_activities' => $activities,
            'header' => $this->loadHeader(),
            'hero_feature' => $this->getHeroFeature(),
            'mode' => $request->input('mode', 'publish'),
            'message' => 'Page settings updated.',
        ]);
    }

    /**
     * Upload an image for an activity. Returns the public URL.
     */
    public function uploadHeroSliderImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('hero-images', 'public');
        $url = '/storage/' . $path;

        return response()->json(['url' => $url]);
    }

    public function uploadHeaderLogo(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('header-logos', 'public');
        $url = '/storage/' . $path;

        return response()->json(['url' => $url]);
    }

    public function uploadActivityImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->store('activity-images', 'public');
        $url = '/storage/' . $path;

        return response()->json(['url' => $url]);
    }

    /**
     * Sync the activities array from the editor into the activities table.
     * Items with an id update; items without an id create; removed items get deleted.
     */
    private function getHeroSliderImages(Headline $headline): array
    {
        $images = SiteSetting::getValue('hero_slider_images', null);
        if ($images === null) {
            return $headline->hero_image_url ? [$headline->hero_image_url] : [];
        }
        if (!is_array($images)) {
            return [];
        }
        $filtered = array_values(array_filter($images, fn ($u) => is_string($u) && trim($u) !== ''));
        return count($filtered) > 0 ? $filtered : [];
    }

    private function getHeroFeature(): array
    {
        $heroFeature = SiteSetting::getValue('hero_feature', null);
        if (!$heroFeature || !is_array($heroFeature)) {
            return [
                'title' => 'Peacebuilding in action',
                'subtitle' => 'Evidence-informed policies • People-centered peacebuilding',
                'intro_title' => 'What makes peacebuilding sustainable?',
                'intro_text' => 'Exploring community-led initiatives that foster long-term stability and social cohesion within the Bangsamoro region.',
                'section_sustainable_title' => 'What makes peacebuilding sustainable?',
                'section_sustainable_content' => 'Sustainable peacebuilding in the Bangsamoro rests on inclusive, community-owned processes that address root causes of conflict while strengthening local institutions. When communities lead their own reconciliation and development initiatives—supported by regional and national frameworks—peace gains lasting legitimacy and resilience.',
                'section_community_title' => 'Community-led initiatives',
                'section_community_content' => 'Across the region, community-led initiatives are fostering long-term stability and social cohesion. These range from local dialogue and dispute-resolution mechanisms to cooperative livelihoods and youth engagement. By centering the voices and agency of affected communities, PSRO and its partners help ensure that peacebuilding is not only about security, but about dignity, opportunity, and shared futures.',
                'section_evidence_title' => 'Evidence-informed policies',
                'section_evidence_content' => 'Policies that last are grounded in evidence and local reality. PSRO supports the BARMM government and partners in using data, research, and community feedback to design and adjust programs. This evidence-informed approach helps direct resources where they are most needed and improves accountability and learning across the peace and security architecture.',
                'section_people_title' => 'People-centered peacebuilding',
                'section_people_content' => 'At the heart of sustainable peace are people and their aspirations. People-centered peacebuilding means putting the safety, rights, and wellbeing of communities first—in policy design, in program delivery, and in measuring success. It also means working across sectors (security, development, reconciliation) so that peace is experienced in everyday life: in schools, livelihoods, governance, and social trust.',
                'tagline' => 'Evidence-informed policies • People-centered peacebuilding',
            ];
        }
        return $heroFeature;
    }

    private function loadHeader(): array
    {
        $header = HeaderSetting::current();
        return [
            'logo_url' => $header->logo_url,
            'org_line1' => $header->org_line1,
            'org_line2' => $header->org_line2,
            'office_title' => $header->office_title,
            'nav_links' => $header->nav_links_json ?? [],
            'social_links' => $header->social_links_json ?? [],
        ];
    }

    private function syncActivities(array $items): void
    {
        $touchedIds = [];

        foreach (array_slice($items, 0, 20) as $order => $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));
            if ($title === '') {
                continue;
            }

            $date = trim((string) ($item['date'] ?? ''));
            $hidden = !empty($item['hidden']);
            $data = [
                'title' => mb_substr($title, 0, 255),
                'category' => mb_substr(trim((string) ($item['category'] ?? 'Program')), 0, 80),
                'description' => mb_substr(trim((string) ($item['description'] ?? '')), 0, 5000),
                'content' => mb_substr(trim((string) ($item['content'] ?? '')), 0, 10000),
                'image' => mb_substr(trim((string) ($item['image'] ?? '')), 0, 512),
                'link' => mb_substr(trim((string) ($item['link'] ?? '#')), 0, 512),
                'date' => $date !== '' ? $date : null,
                'sort_order' => $order,
                'is_published' => !$hidden,
            ];

            if (!empty($item['id']) && ($activity = Activity::find($item['id']))) {
                $activity->update($data);
                $touchedIds[] = $activity->id;
            } else {
                $activity = Activity::create($data);
                $touchedIds[] = $activity->id;
            }
        }

        // Delete activities that were removed from the editor
        if (count($touchedIds) > 0) {
            Activity::whereNotIn('id', $touchedIds)->delete();
        } else {
            Activity::query()->delete();
        }
    }

    private function loadFooterGroup(string $group): array
    {
        return FooterLink::where('group', $group)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'url' => $l->url,
            ])
            ->values()
            ->toArray();
    }

    private function syncFooterLinks(string $group, array $items): void
    {
        $touchedIds = [];

        foreach (array_slice($items, 0, 30) as $order => $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $data = [
                'group' => $group,
                'name' => mb_substr($name, 0, 255),
                'url' => mb_substr(trim((string) ($item['url'] ?? '#')), 0, 512),
                'sort_order' => $order,
            ];

            if (!empty($item['id']) && ($link = FooterLink::where('group', $group)->find($item['id']))) {
                $link->update($data);
                $touchedIds[] = $link->id;
            } else {
                $link = FooterLink::create($data);
                $touchedIds[] = $link->id;
            }
        }

        $query = FooterLink::where('group', $group);
        if (count($touchedIds) > 0) {
            $query->whereNotIn('id', $touchedIds)->delete();
        } else {
            $query->delete();
        }
    }

    /**
     * Delete a file from public storage given a path like /storage/hero-images/xyz.jpg
     */
    private function deleteStorageFile(string $urlOrPath): void
    {
        try {
            if (str_contains($urlOrPath, '/storage/')) {
                $path = str_replace('/storage/', '', parse_url($urlOrPath, PHP_URL_PATH) ?? $urlOrPath);
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to delete storage file: ' . $e->getMessage());
        }
    }
}
