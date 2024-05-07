<?php

namespace Shoptet\Categories;

use Shoptet\Import\Exceptions\RedisImportLockException;
use Shoptet\Import\Helper\RedisImportLockHelper;
use Shoptet\Layout\Helper\SectionMapService;
use Shoptet\Layout\Model\Page;
use Shoptet\Priority\PriorityBuilder;

class CategoryRequestDataService
{
    private const SORT_TYPE_BEFORE = 'sortBefore';
    private const SORT_TYPE_AFTER = 'sortAfter';

    /** @var int[] */
    private $categoryIdsSortedByPriority = [];

    /** @var SectionMapService */
    private $sectionMapService;

    public function __construct(
        SectionMapService $sectionMapService
    ) {
        $this->sectionMapService = $sectionMapService;
    }

    public function getCategoryPosition(array $data, int $categoryId, ?int $categoryParentId = null): ?int
    {
        /** @var int $currentCategoryParentId */
        $currentCategoryParentId = $this->sectionMapService->getCategoryParentIdById($categoryId, Page::CATEGORY_PAGE_TYPE);

        if ($categoryParentId === null) {
            $categoryParentId = $currentCategoryParentId;
        }

        $categoryIds = $this->getCategoryIdsSortedByPriority($categoryParentId);

        // Force it to place category at the end, important for parent category change
        if ($currentCategoryParentId !== $categoryParentId) {
            return count($categoryIds);
        }

        $sortTypeKey = $this->getSortTypeKey($data);

        if (!$sortTypeKey) {
            return null;
        }

        $newSorting = $this->sortCategories($data, $sortTypeKey, $categoryId, $categoryParentId);
        $newPosition = array_search($categoryId, $newSorting, true);

        if ($newPosition === false) {
            return null;
        }

        $oldPosition = array_search($categoryId, $categoryIds, true);

        if ($oldPosition < $newPosition) {
            return $newPosition + 1;
        }

        return $newPosition;
    }

    public function updateCategoryPosition(array $data, int $categoryId): void
    {
        $sortTypeKey = $this->getSortTypeKey($data);

        if (!$sortTypeKey) {
            return;
        }

        /** @var int $categoryParentId */
        $categoryParentId = $this->sectionMapService->getCategoryParentIdById($categoryId, Page::CATEGORY_PAGE_TYPE);
        $newSorting = $this->sortCategories($data, $sortTypeKey, $categoryId, $categoryParentId);

        $priority = PriorityBuilder::buildCategory($categoryParentId, Page::CATEGORY_PAGE_TYPE);
        $priority->update($newSorting);
    }

    /**
     * @throws RedisImportLockException
     * @throws CategoryRequestDataException
     */
    public function validate(array $data, ?int $parentId, ?int $categoryId = null): void
    {
        $sortTypeKey = $this->getSortTypeKey($data);

        if ($sortTypeKey || array_key_exists('parentGuid', $data)) {
            if (RedisImportLockHelper::getInstance()->isLocked()) {
                throw new RedisImportLockException(
                    'Changing "sortBefore", "sortAfter" or "parentGuid" is currently disabled due to running import.'
                );
            }
        }

        if ($sortTypeKey) {
            $sortCategory = $this->sectionMapService->getCategoryByGuid($data[$sortTypeKey], Page::CATEGORY_PAGE_TYPE);

            if (!$sortCategory) {
                throw new CategoryRequestDataException(
                    sprintf('Unknown category guid in "%s".', $sortTypeKey),
                    sprintf('data.%s', $sortTypeKey)
                );
            }

            if ($sortCategory->id === $categoryId) {
                throw new CategoryRequestDataException(
                    sprintf(
                        'It is not possible to use category "%s" from "%s" for sorting as you are editing the same category.',
                        $data[$sortTypeKey],
                        $sortTypeKey
                    ),
                    sprintf('data.%s', $sortTypeKey)
                );
            }

            if (($parentId !== null && $sortCategory->parentId !== $parentId) || ($parentId === null && $sortCategory->parentId !== Page::INDEX_PAGE_ID)) {
                throw new CategoryRequestDataException(
                    sprintf(
                        'It is not possible to use category "%s" from "%s" for sorting as it does not belong to the same parent as this category.',
                        $data[$sortTypeKey],
                        $sortTypeKey
                    ),
                    sprintf('data.%s', $sortTypeKey)
                );
            }
        }
    }

    private function getSortTypeKey(array $data): ?string
    {
        if (array_key_exists(self::SORT_TYPE_BEFORE, $data)) {
            return self::SORT_TYPE_BEFORE;
        }

        if (array_key_exists(self::SORT_TYPE_AFTER, $data)) {
            return self::SORT_TYPE_AFTER;
        }

        return null;
    }

    private function sortCategories(array $data, string $sortTypeKey, int $categoryId, int $categoryParentId): array
    {
        $categoryIds = $this->getCategoryIdsSortedByPriority($categoryParentId);
        $sortCategoryId = $this->sectionMapService->getCategoryIdByGuid($data[$sortTypeKey], Page::CATEGORY_PAGE_TYPE);
        $result = [];

        // Remove category for which we want to change position
        $position = array_search($categoryId, $categoryIds, true);
        unset($categoryIds[$position]);

        foreach ($categoryIds as $id) {
            // Category is same as category used in "sortBefore" or "sortAfter"
            $isSameCategory = $id === $sortCategoryId;

            if ($isSameCategory && $sortTypeKey === self::SORT_TYPE_BEFORE) {
                $result[] = $categoryId;
            }

            $result[] = $id;

            if ($isSameCategory && $sortTypeKey === self::SORT_TYPE_AFTER) {
                $result[] = $categoryId;
            }
        }

        return $result;
    }

    /**
     * @return int[]
     */
    private function getCategoryIdsSortedByPriority(int $categoryParentId): array
    {
        if (!$this->categoryIdsSortedByPriority) {
            $this->categoryIdsSortedByPriority = PriorityBuilder::buildCategory(
                $categoryParentId,
                Page::CATEGORY_PAGE_TYPE
            )->pairs('id', 'id');
        }

        return $this->categoryIdsSortedByPriority;
    }

}
