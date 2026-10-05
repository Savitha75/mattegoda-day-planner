<?php
declare(strict_types=1);

final class Place
{
    /** Allowed sort orders. Only these fixed strings can ever reach ORDER BY (FR-03). */
    private const SORTS = [
        'distance_asc'  => 'p.distance_km ASC, p.name ASC',
        'distance_desc' => 'p.distance_km DESC, p.name ASC',
        'name'          => 'p.name ASC',
    ];

    public readonly int $placeId;
    public readonly string $name;
    public readonly string $summary;
    public readonly ?float $distanceKm;
    /** @var int[]  primary category first */
    public readonly array $categoryIds;
    public readonly ?string $thumbPath;

    private function __construct(array $row)
    {
        $this->placeId     = (int) $row['place_id'];
        $this->name        = $row['name'];
        $this->summary     = $row['summary'];
        $this->distanceKm  = $row['distance_km'] !== null ? (float) $row['distance_km'] : null;
        $this->categoryIds = $row['category_ids'] !== null
            ? array_map('intval', explode(',', $row['category_ids']))
            : [];
        $this->thumbPath   = $row['thumb_path'];
    }

    /**
     * Active places with their categories and first photo (FR-01, FR-02).
     * @return Place[]
     */
    public static function findAll(array $filters = [], string $sort = 'distance_asc'): array
    {
        $orderBy = self::SORTS[$sort] ?? self::SORTS['distance_asc'];
        $where   = ['p.is_active = 1'];
        $params  = [];
        // Next step: category (FR-06 to FR-08) and keyword (FR-09, FR-10) conditions are added here.

        $sql = 'SELECT p.place_id, p.name, p.summary, p.distance_km,
                       GROUP_CONCAT(pc.category_id ORDER BY pc.is_primary DESC, pc.category_id) AS category_ids,
                       (SELECT ph.file_path
                          FROM place_photo ph
                         WHERE ph.place_id = p.place_id
                         ORDER BY ph.sort_order, ph.photo_id
                         LIMIT 1) AS thumb_path
                  FROM place p
                  LEFT JOIN place_category pc ON pc.place_id = p.place_id
                 WHERE ' . implode(' AND ', $where) . '
                 GROUP BY p.place_id, p.name, p.summary, p.distance_km
                 ORDER BY ' . $orderBy;

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);

        return array_map(fn(array $row) => new self($row), $stmt->fetchAll());
    }

    /** @return Category[]  this place's categories, primary first */
    public function getCategories(): array
    {
        $all = Category::findAll();
        return array_values(array_filter(
            array_map(fn(int $id) => $all[$id] ?? null, $this->categoryIds)
        ));
    }
}