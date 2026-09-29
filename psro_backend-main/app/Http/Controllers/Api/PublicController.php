<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FooterLink;
use App\Models\HeaderSetting;
use App\Models\Headline;
use App\Models\Mandate;
use App\Models\Post;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function index(): JsonResponse
    {
        $maintenanceMode = SiteSetting::getValue('maintenance_mode', false);

        if ($maintenanceMode) {
            return response()->json([
                'maintenance' => true,
                'maintenance_message' => SiteSetting::getValue('maintenance_message', ''),
                'maintenance_estimated_back' => SiteSetting::getValue('maintenance_estimated_back', ''),
            ]);
        }

        // Hero slider — posts marked as slider
        $slides = Post::published()
            ->slider()
            ->whereNotNull('image_path')
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'image' => $post->image_path,
                'title' => $post->title,
                'description' => $post->description,
                'category' => $post->category,
                'cta_text' => $post->cta_text,
            ]);

        // Headline from its own table
        $headline = Headline::current();

        // Mandate from its own table
        $mandate = Mandate::current();

        // Programs & Activities — published posts (non-slider, to avoid overlap with hero)
        $activities = Post::published()
            ->where('is_slider', false)
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'description' => $post->description ?? '',
                'highlight_name' => $post->highlight_name ?? '',
                'highlight_role' => $post->highlight_role ?? '',
                'highlight_quote' => $post->highlight_quote ?? '',
                'content' => $post->content ?? '',
                'category' => $post->category,
                'image' => $post->image_path,
                'link' => $post->cta_link ?: '#',
                'videos' => $post->videos ?? [],
                'date' => $post->activity_display_date
                    ? $post->activity_display_date->format('Y-m-d')
                    : (($post->published_at ?? $post->created_at)?->format('Y-m-d')),
            ])
            ->values();

        $footerMinistries = FooterLink::ministries()->get()->map(fn ($l) => ['name' => $l->name, 'url' => $l->url]);
        $footerAgencies = FooterLink::agencies()->get()->map(fn ($l) => ['name' => $l->name, 'url' => $l->url]);

        $header = HeaderSetting::current();

        $activitySectionLayout = SiteSetting::getValue('activity_section_layout', 'card');
        $activitySectionLabel = SiteSetting::getValue('activity_section_label', 'Institutional Portfolio');
        $activitySectionHeadline = SiteSetting::getValue('activity_section_headline', 'Recent Programs & Activities');
        $activitySectionDesign = SiteSetting::getValue('activity_section_design', 'default');
        $activityDisplayLimit = (int) SiteSetting::getValue('activity_display_limit', 7);
        $postShowDates = filter_var(SiteSetting::getValue('post_show_dates', true), FILTER_VALIDATE_BOOLEAN);
        $postShowReadMore = filter_var(SiteSetting::getValue('post_show_read_more', true), FILTER_VALIDATE_BOOLEAN);
        $postShowNoImagePlaceholder = filter_var(SiteSetting::getValue('post_show_no_image_placeholder', true), FILTER_VALIDATE_BOOLEAN);
        $postDescriptionMaxLength = (int) SiteSetting::getValue('post_description_max_length', 500);

        $problemSection = $this->getProblemSection();
        $servicesSection = $this->getServicesSection();
        $testimonialsSection = $this->getTestimonialsSection();
        $aboutUsSection = $this->getAboutUsSection();
        $faqSection = $this->getFaqSection();
        $finalCtaSection = $this->getFinalCtaSection();
        $headerCta = $this->getHeaderCta();

        $impactStats = $this->getImpactStats();
        $marqueeItems = $this->getMarqueeItems();
        $sectionVisibility = $this->getSectionVisibility();

        $heroFeature = SiteSetting::getValue('hero_feature', null);
        if (!$heroFeature || !is_array($heroFeature)) {
            $heroFeature = [
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

        $heroSliderImages = $this->getHeroSliderImages($headline);

        return response()->json([
            'slides' => $slides,
            'headline' => $headline->headline,
            'subheadline' => $headline->sub_headline,
            'hero_image_url' => $this->resolveStorageUrl($headline->hero_image_url),
            'hero_slider_images' => array_map(fn ($u) => $this->resolveStorageUrl($u), $heroSliderImages),
            'mandate' => [
                'headline' => $mandate->headline,
                'subheadline' => $mandate->subheadline,
                'director_quote' => $mandate->director_quote,
                'director_name' => $mandate->director_name,
                'director_title' => $mandate->director_title,
                'director_image_url' => $mandate->director_image_url ?? '',
            ],
            'activities' => $activities->values(),
            'activity_section_layout' => $activitySectionLayout,
            'activity_section_label' => $activitySectionLabel,
            'activity_section_headline' => $activitySectionHeadline,
            'activity_section_design' => $activitySectionDesign,
            'activity_display_limit' => $activityDisplayLimit,
            'post_show_dates' => $postShowDates,
            'post_show_read_more' => $postShowReadMore,
            'post_show_no_image_placeholder' => $postShowNoImagePlaceholder,
            'post_description_max_length' => $postDescriptionMaxLength,
            'footer_ministries' => $footerMinistries->values(),
            'footer_agencies' => $footerAgencies->values(),
            'header' => [
                'logo_url' => $header->logo_url,
                'org_line1' => $header->org_line1,
                'org_line2' => $header->org_line2,
                'office_title' => $header->office_title,
                'nav_links' => $this->getNavLinks($header),
                'social_links' => $this->getSocialLinks($header),
                'cta_text' => $headerCta['cta_text'] ?? 'Contact Us',
                'show_cta' => $headerCta['show_cta'] ?? true,
            ],
            'hero_feature' => $heroFeature,
            'impact_stats' => $impactStats,
            'marquee_items' => $marqueeItems,
            'section_visibility' => $sectionVisibility,
            'problem_section' => $problemSection,
            'services_section' => $servicesSection,
            'testimonials_section' => $testimonialsSection,
            'about_us_section' => $aboutUsSection,
            'faq_section' => $faqSection,
            'final_cta_section' => $finalCtaSection,
        ]);
    }

    private function getHeroSliderImages(Headline $headline): array
    {
        $setting = SiteSetting::where('key', 'hero_slider_images')->first();
        if (!$setting) {
            return $headline->hero_image_url ? [$headline->hero_image_url] : [];
        }
        $images = json_decode($setting->value, true);
        if (!is_array($images)) {
            return $headline->hero_image_url ? [$headline->hero_image_url] : [];
        }
        $filtered = array_values(array_filter($images, fn ($u) => is_string($u) && trim($u) !== ''));
        return count($filtered) > 0 ? $filtered : ($headline->hero_image_url ? [$headline->hero_image_url] : []);
    }

    /**
     * Resolve storage path to full API URL for frontend consumption.
     * E.g. /storage/hero-images/xxx.jpg -> https://domain.com/api/storage/hero-images/xxx.jpg
     * /images/pic1.jpg (default placeholder) -> Unsplash fallback (file may not exist)
     */
    private function resolveStorageUrl(?string $path): ?string
    {
        if (!$path || !is_string($path)) {
            return $path;
        }
        $path = trim($path);
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        if (str_starts_with($path, '/storage/')) {
            $storagePath = substr($path, strlen('/storage'));
            return url('/api/storage/' . ltrim($storagePath, '/'));
        }
        // Default placeholder /images/pic1.jpg often doesn't exist; use working fallback
        if ($path === '/images/pic1.jpg') {
            return 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?auto=format&fit=crop&q=80&w=2070';
        }
        if (str_starts_with($path, '/images/')) {
            return url($path);
        }
        return $path;
    }

    private function getProblemSection(): array
    {
        $data = SiteSetting::getValue('problem_section', null);
        if (!$data || !is_array($data)) {
            return [
                'headline' => 'The challenges we address',
                'pain_points' => [
                    'Fragmented coordination across peace, security, and development actors in the region.',
                    'Communities facing conflict and displacement without adequate support systems.',
                    'Limited evidence-based policy frameworks for sustainable peacebuilding.',
                ],
            ];
        }
        return array_merge([
            'headline' => 'The challenges we address',
            'pain_points' => [],
        ], $data);
    }

    private function getServicesSection(): array
    {
        $data = SiteSetting::getValue('services_section', null);
        if (!$data || !is_array($data)) {
            return [
                'headline' => 'What we do',
                'subheadline' => 'Our services address the challenges of peacebuilding across the Bangsamoro region.',
                'services' => [
                    ['title' => 'Peace & Security Coordination', 'description' => 'Serving as the primary facilitator for coherence across the BARMM system.', 'icon' => 'shield'],
                    ['title' => 'Conflict Transformation', 'description' => 'Driving impactful system-wide action through strategic advice and policy guidance.', 'icon' => 'hand'],
                    ['title' => 'Evidence-Informed Policy Support', 'description' => 'Supporting data-driven program design and adjustment for lasting impact.', 'icon' => 'chart'],
                    ['title' => 'Community Engagement', 'description' => 'Centering the voices and agency of affected communities in peacebuilding.', 'icon' => 'group'],
                ],
            ];
        }
        return array_merge([
            'headline' => 'What we do',
            'subheadline' => '',
            'services' => [],
        ], $data);
    }

    private function getTestimonialsSection(): array
    {
        $data = SiteSetting::getValue('testimonials_section', null);
        if (!$data || !is_array($data)) {
            return [
                'headline' => 'What partners say',
                'subheadline' => 'Stakeholders and partners share their experience working with PSRO-BARMM.',
                'testimonials' => [
                    ['quote' => 'PSRO has been instrumental in bringing together different actors for a coordinated approach to peace and security.', 'name' => 'Partner Representative', 'role' => 'Local Government Unit', 'image_url' => '/images/lgu.png'],
                    ['quote' => 'The evidence-informed approach helps us direct resources where they are most needed.', 'name' => 'Development Partner', 'role' => 'International NGO', 'image_url' => '/images/360_F_1091183481_lrB77TJUEMGmIpGZyYizS864CzsNf37J.jpg'],
                    ['quote' => 'Inclusive dialogue with communities and clearer follow-through across agencies have strengthened trust in joint peace and development efforts.', 'name' => 'Civil Society Representative', 'role' => 'People\'s Organization', 'image_url' => '/images/community-network-and-social-logo-design-vector%20(1).jpg'],
                ],
            ];
        }
        return array_merge([
            'headline' => 'What partners say',
            'subheadline' => '',
            'testimonials' => [],
        ], $data);
    }

    private function getAboutUsSection(): array
    {
        $data = SiteSetting::getValue('about_us_section', null);
        if (!$data || !is_array($data)) {
            return [
                'headline' => 'About PSRO',
                'short_text' => 'The Peace, Security & Reconciliation Office serves as the primary facilitator for coherence across the BARMM system, working horizontally across ministries and vertically from regional to community level.',
                'mandate_text' => '',
                'technical_details' => [
                    'scope' => 'Horizontal coordination across all BARMM ministries and agencies.',
                    'reach' => 'Vertical integration from the regional seat to barangay communities.',
                    'approach' => 'Whole-of-government facilitation for peace and normalization.',
                    'status_prefix' => 'Operational · ',
                    'status_emphasis' => 'Active',
                ],
                'team_photos' => [],
            ];
        }
        return array_merge([
            'headline' => 'About PSRO',
            'short_text' => '',
            'mandate_text' => '',
            'technical_details' => [],
            'team_photos' => [],
        ], $data);
    }

    private function getFaqSection(): array
    {
        $data = SiteSetting::getValue('faq_section', null);
        if (!$data || !is_array($data)) {
            return [
                'headline' => 'Frequently Asked Questions',
                'subheadline' => 'Common questions about PSRO-BARMM and our work.',
                'items' => [
                    ['question' => 'What is PSRO-BARMM?', 'answer' => 'The Peace, Security & Reconciliation Office (PSRO) is a BARMM government office that serves as the primary facilitator for coherence across the peace, security, and development system in the Bangsamoro region.'],
                    ['question' => 'How can I partner with PSRO?', 'answer' => 'You can reach out through our contact form or official channels. We work with local government units, civil society, and development partners on peacebuilding initiatives.'],
                    ['question' => 'Where does PSRO operate?', 'answer' => 'PSRO operates across the Bangsamoro Autonomous Region in Muslim Mindanao, working at regional, provincial, and community levels.'],
                ],
            ];
        }
        return array_merge([
            'headline' => 'Frequently Asked Questions',
            'subheadline' => '',
            'items' => [],
        ], $data);
    }

    private function getFinalCtaSection(): array
    {
        $data = SiteSetting::getValue('final_cta_section', null);
        if (!$data || !is_array($data)) {
            return [
                'headline' => 'Ready to partner with us?',
                'subheadline' => 'Get in touch to learn how we can work together for lasting peace in the Bangsamoro region.',
                'button_text' => 'Contact Us',
            ];
        }
        return array_merge([
            'headline' => 'Ready to partner with us?',
            'subheadline' => '',
            'button_text' => 'Contact Us',
        ], $data);
    }

    private function getHeaderCta(): array
    {
        $data = SiteSetting::getValue('header_cta', null);
        if (!$data || !is_array($data)) {
            return ['cta_text' => 'Contact Us', 'show_cta' => true];
        }
        return array_merge(['cta_text' => 'Contact Us', 'show_cta' => true], $data);
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
        $items = [];
        foreach ($data as $item) {
            $s = is_string($item) ? trim($item) : (is_array($item) && isset($item['text']) ? trim($item['text']) : '');
            if ($s !== '') $items[] = $s;
        }
        return $items;
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

    private function getSocialLinks(HeaderSetting $header): array
    {
        $defaults = [
            ['platform' => 'facebook', 'url' => 'https://www.facebook.com/psro.ocm'],
            ['platform' => 'youtube', 'url' => 'https://youtube.com'],
        ];
        $links = $header->social_links_json ?? [];
        if (! is_array($links) || count($links) === 0) {
            return $defaults;
        }
        foreach ($links as &$link) {
            if (! is_array($link) || empty($link['platform'])) {
                continue;
            }
            if ($link['platform'] === 'facebook') {
                $url = trim($link['url'] ?? '');
                if ($url === '' || $url === 'https://facebook.com' || $url === 'https://www.facebook.com') {
                    $link['url'] = 'https://www.facebook.com/psro.ocm';
                }
            }
        }
        return $links;
    }

    private function getNavLinks(HeaderSetting $header): array
    {
        $links = $header->nav_links_json ?? [];
        if (! is_array($links) || count($links) === 0) {
            return [
                ['label' => 'Home', 'url' => '/', 'children' => []],
                ['label' => 'About Us', 'url' => '#about', 'children' => []],
                ['label' => 'Programs', 'url' => '#section-activities', 'children' => []],
                ['label' => 'Contact', 'url' => '#contact', 'children' => []],
            ];
        }
        return $links;
    }
}
