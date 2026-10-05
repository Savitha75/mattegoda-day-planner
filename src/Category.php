<?php
declare(strict_types=1);

final class Category
{
    /** @var array<int, Category>|null  loaded once per request, then reused */
    private static ?array $cache = null;

    private function __construct(
        public readonly int $categoryId,
        public readonly string $name,
        public readonly string $badgeColour,
        public readonly ?string $markerIcon,
    ) {}

    /** All 7 categories, keyed by category_id. */
    public static function findAll(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            $rows = Database::getConnection()
                ->query('SELECT category_id, name, badge_colour, marker_icon FROM category ORDER BY category_id')
                ->fetchAll();

            foreach ($rows as $r) {
                self::$cache[(int) $r['category_id']] = new self(
                    (int) $r['category_id'],
                    $r['name'],
                    $r['badge_colour'],
                    $r['marker_icon']
                );
            }
        }
        return self::$cache;
    }

    public static function findById(int $id): ?self
    {
        return self::findAll()[$id] ?? null;
    }
}