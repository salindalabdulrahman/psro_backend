<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'maintenance_mode' => SiteSetting::getValue('maintenance_mode', false),
            'maintenance_message' => SiteSetting::getValue('maintenance_message', ''),
            'maintenance_estimated_back' => SiteSetting::getValue('maintenance_estimated_back', ''),
            'activity_section_layout' => SiteSetting::getValue('activity_section_layout', 'card'),
            'activity_section_label' => SiteSetting::getValue('activity_section_label', 'Institutional Portfolio'),
            'activity_section_headline' => SiteSetting::getValue('activity_section_headline', 'Recent Programs & Activities'),
            'activity_section_design' => SiteSetting::getValue('activity_section_design', 'default'),
            'activity_display_limit' => (int) SiteSetting::getValue('activity_display_limit', 7),
            'post_show_dates' => filter_var(SiteSetting::getValue('post_show_dates', true), FILTER_VALIDATE_BOOLEAN),
            'post_show_read_more' => filter_var(SiteSetting::getValue('post_show_read_more', true), FILTER_VALIDATE_BOOLEAN),
            'post_show_no_image_placeholder' => filter_var(SiteSetting::getValue('post_show_no_image_placeholder', true), FILTER_VALIDATE_BOOLEAN),
            'post_description_max_length' => (int) SiteSetting::getValue('post_description_max_length', 500),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'maintenance_mode' => ['required', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:1000'],
            'maintenance_estimated_back' => ['nullable', 'string', 'max:255'],
            'activity_section_layout' => ['nullable', 'string', 'in:card,compact,masonry,timeline'],
        ]);

        SiteSetting::setValue('maintenance_mode', $request->boolean('maintenance_mode') ? '1' : '0', 'boolean');

        if ($request->has('maintenance_message')) {
            SiteSetting::setValue('maintenance_message', trim((string) $request->input('maintenance_message', '')), 'text');
        }

        if ($request->has('maintenance_estimated_back')) {
            SiteSetting::setValue('maintenance_estimated_back', trim((string) $request->input('maintenance_estimated_back', '')), 'text');
        }

        if ($request->has('activity_section_layout')) {
            SiteSetting::setValue('activity_section_layout', trim((string) $request->input('activity_section_layout', 'card')), 'text');
        }

        return response()->json([
            'maintenance_mode' => SiteSetting::getValue('maintenance_mode', false),
            'maintenance_message' => SiteSetting::getValue('maintenance_message', ''),
            'maintenance_estimated_back' => SiteSetting::getValue('maintenance_estimated_back', ''),
            'activity_section_layout' => SiteSetting::getValue('activity_section_layout', 'card'),
            'activity_display_limit' => (int) SiteSetting::getValue('activity_display_limit', 7),
            'message' => 'Settings updated.',
        ]);
    }

    public function updateActivitySectionLayout(Request $request): JsonResponse
    {
        $request->validate([
            'layout' => ['required', 'string', 'in:card,compact,masonry,timeline'],
        ]);

        SiteSetting::setValue('activity_section_layout', $request->input('layout'), 'text');

        return response()->json([
            'activity_section_layout' => SiteSetting::getValue('activity_section_layout', 'card'),
            'message' => 'Layout updated.',
        ]);
    }

    public function updateActivitySection(Request $request): JsonResponse
    {
        $request->validate([
            'layout' => ['nullable', 'string', 'in:card,compact,masonry,timeline'],
            'label' => ['nullable', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:255'],
            'design' => ['nullable', 'string', 'in:default,minimal,bold'],
            'display_limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        if ($request->has('layout')) {
            SiteSetting::setValue('activity_section_layout', $request->input('layout'), 'text');
        }
        if ($request->has('label')) {
            SiteSetting::setValue('activity_section_label', trim((string) $request->input('label', '')), 'text');
        }
        if ($request->has('headline')) {
            SiteSetting::setValue('activity_section_headline', trim((string) $request->input('headline', '')), 'text');
        }
        if ($request->has('design')) {
            SiteSetting::setValue('activity_section_design', $request->input('design'), 'text');
        }
        if ($request->has('display_limit')) {
            $limit = (int) $request->input('display_limit');
            $limit = max(1, min(24, $limit));
            SiteSetting::setValue('activity_display_limit', (string) $limit, 'text');
        }

        return response()->json([
            'activity_section_layout' => SiteSetting::getValue('activity_section_layout', 'card'),
            'activity_section_label' => SiteSetting::getValue('activity_section_label', 'Institutional Portfolio'),
            'activity_section_headline' => SiteSetting::getValue('activity_section_headline', 'Recent Programs & Activities'),
            'activity_section_design' => SiteSetting::getValue('activity_section_design', 'default'),
            'activity_display_limit' => (int) SiteSetting::getValue('activity_display_limit', 7),
            'message' => 'Activity section updated.',
        ]);
    }

    public function updatePostVisibility(Request $request): JsonResponse
    {
        $request->validate([
            'show_dates' => ['nullable', 'boolean'],
            'show_read_more' => ['nullable', 'boolean'],
            'show_no_image_placeholder' => ['nullable', 'boolean'],
            'description_max_length' => ['nullable', 'integer', 'min:50', 'max:2000'],
        ]);

        if ($request->has('show_dates')) {
            SiteSetting::setValue('post_show_dates', $request->boolean('show_dates') ? '1' : '0', 'boolean');
        }
        if ($request->has('show_read_more')) {
            SiteSetting::setValue('post_show_read_more', $request->boolean('show_read_more') ? '1' : '0', 'boolean');
        }
        if ($request->has('show_no_image_placeholder')) {
            SiteSetting::setValue('post_show_no_image_placeholder', $request->boolean('show_no_image_placeholder') ? '1' : '0', 'boolean');
        }
        if ($request->has('description_max_length')) {
            $len = max(50, min(2000, (int) $request->input('description_max_length')));
            SiteSetting::setValue('post_description_max_length', (string) $len, 'text');
        }

        return response()->json([
            'post_show_dates' => filter_var(SiteSetting::getValue('post_show_dates', true), FILTER_VALIDATE_BOOLEAN),
            'post_show_read_more' => filter_var(SiteSetting::getValue('post_show_read_more', true), FILTER_VALIDATE_BOOLEAN),
            'post_show_no_image_placeholder' => filter_var(SiteSetting::getValue('post_show_no_image_placeholder', true), FILTER_VALIDATE_BOOLEAN),
            'post_description_max_length' => (int) SiteSetting::getValue('post_description_max_length', 500),
            'message' => 'Post visibility updated.',
        ]);
    }
}
