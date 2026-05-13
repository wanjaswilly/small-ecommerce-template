<?php

namespace App\Services;

use App\Models\Page;

class CMSService
{
    public function getAllPages(): array
    {
        return Page::orderBy('sort_order')->get()->toArray();
    }

    public function getActivePages(): array
    {
        return Page::where('is_active', true)->orderBy('sort_order')->get()->toArray();
    }

    public function getHomepage(): ?array
    {
        $page = Page::where('is_homepage', true)->where('is_active', true)->first();
        return $page ? $page->toArray() : null;
    }

    public function getPageBySlug(string $slug): ?array
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->first();
        return $page ? $page->toArray() : null;
    }

    public function createPage(array $data): Page
    {
        return Page::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'content' => $data['content'] ?? '',
            'excerpt' => $data['excerpt'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_homepage' => $data['is_homepage'] ?? false,
            'meta_title' => $data['meta_title'] ?? $data['title'],
            'meta_description' => $data['meta_description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function updatePage(int $id, array $data): bool
    {
        $page = Page::find($id);
        if (!$page) {
            return false;
        }

        if (isset($data['is_homepage']) && $data['is_homepage']) {
            Page::where('id', '!=', $id)->update(['is_homepage' => false]);
        }

        return $page->update($data);
    }

    public function deletePage(int $id): bool
    {
        $page = Page::find($id);
        if (!$page) {
            return false;
        }
        return $page->delete();
    }
}