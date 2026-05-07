<?php

namespace Ksfraser\FA_ProductAttributes\Actions;

use Ksfraser\FA_ProductAttributes\Dao\ProductTagsDao;

/**
 * Single Responsibility: Creates or updates a global product tag.
 *
 * Expected POST keys:
 *   name    string  Required; human-readable tag name
 *   slug    string  Optional; auto-generated from name if empty
 *   tag_id  int     0 → INSERT new tag; >0 → UPDATE existing tag
 */
class UpsertTagAction
{
    /** @var ProductTagsDao */
    private $dao;

    public function __construct(ProductTagsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array<string, mixed> $postData
     * @return string Result message
     */
    public function handle(array $postData): string
    {
        $name = trim((string)($postData['name'] ?? ''));
        if ($name === '') {
            return 'Tag name is required';
        }

        $rawSlug = trim((string)($postData['slug'] ?? ''));
        if ($rawSlug === '') {
            $rawSlug = strtolower($name);
            $rawSlug = preg_replace('/[^a-z0-9]+/', '-', $rawSlug);
            $rawSlug = trim($rawSlug, '-');
        }
        // Sanitise: lowercase alpha-numeric + hyphens only
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($rawSlug));
        $slug = trim($slug, '-');
        if ($slug === '') {
            return 'Invalid tag slug';
        }

        $tagId = (int)($postData['tag_id'] ?? 0);

        $this->dao->upsertTag($name, $slug, $tagId);

        return $tagId > 0 ? _('Tag updated') : _('Tag created');
    }
}
