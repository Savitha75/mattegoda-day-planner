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

    // Detail fields: filled by findById(); null in list results
    public readonly ?string $description;
    public readonly ?float $latitude;
    public readonly ?float $longitude;
    public readonly ?string $accessPoint;
    public readonly ?string $openingTime;
    public readonly ?string $closingTime;
    /** @var string[]  e.g. ['Mon', 'Poya'] */
    public readonly array $closedDays;
    public readonly ?string $entryFee;
    public readonly ?string $bestTime;
    public readonly ?int $visitDurationMin;
    public readonly ?string $travelTips;
    public readonly ?string $facilities;
    public readonly ?int $travelMin;
    public readonly ?string $infoSource;
    public readonly ?string $updatedAt;
    public readonly ?string $createdAt;    // FR-53
    public readonly bool $isActive;        // FR-51: false = deleted (hidden from visitors)
    public readonly ?float $nearbyKm;      // straight-line km from another place (findNearby only)

    private function __construct(array $row)
    {
        // Small helpers: missing or NULL columns become null
        $float = fn(string $k): ?float => isset($row[$k]) ? (float) $row[$k] : null;
        $int   = fn(string $k): ?int   => isset($row[$k]) ? (int) $row[$k] : null;

        $this->placeId     = (int) $row['place_id'];
        $this->name        = $row['name'];
        $this->summary     = $row['summary'] ?? '';
        $this->distanceKm  = $float('distance_km');
        $this->categoryIds = isset($row['category_ids'])
            ? array_map('intval', explode(',', $row['category_ids']))
            : [];
        $this->thumbPath   = $row['thumb_path'] ?? null;

        $this->description      = $row['description'] ?? null;
        $this->latitude         = $float('latitude');
        $this->longitude        = $float('longitude');
        $this->accessPoint      = $row['access_point'] ?? null;
        $this->openingTime      = $row['opening_time'] ?? null;
        $this->closingTime      = $row['closing_time'] ?? null;
        $this->closedDays       = !empty($row['closed_days']) ? explode(',', $row['closed_days']) : [];
        $this->entryFee         = $row['entry_fee'] ?? null;
        $this->bestTime         = $row['best_time'] ?? null;
        $this->visitDurationMin = $int('visit_duration_min');
        $this->travelTips       = $row['travel_tips'] ?? null;
        $this->facilities       = $row['facilities'] ?? null;
        $this->travelMin        = $int('travel_min');
        $this->infoSource       = $row['info_source'] ?? null;
        $this->updatedAt        = $row['updated_at'] ?? null;
        $this->createdAt        = $row['created_at'] ?? null;
        $this->isActive         = (int) ($row['is_active'] ?? 1) === 1;
        $this->nearbyKm         = $float('nearby_km');
    }

    /**
     * Active places with their categories and first photo (FR-01, FR-02),
     * optionally filtered by category and keyword (FR-06 to FR-10).
     * @param array{categories?: int[], q?: string} $filters
     * @return Place[]
     */
    public static function findAll(array $filters = [], string $sort = 'distance_asc'): array
    {
        $orderBy = self::SORTS[$sort] ?? self::SORTS['distance_asc'];
        $where   = ['p.is_active = 1'];
        $params  = [];

        // FR-06 to FR-08: places in ANY of the selected categories
        $categoryIds = $filters['categories'] ?? [];
        if ($categoryIds !== []) {
            $marks   = implode(',', array_fill(0, count($categoryIds), '?'));   // e.g. "?,?"
            $where[] = "p.place_id IN (SELECT pc2.place_id
                                         FROM place_category pc2
                                        WHERE pc2.category_id IN ($marks))";
            array_push($params, ...$categoryIds);
        }

        // FR-09: keyword in name or description (trimmed; case-insensitive via the _ci collation)
        $keyword = trim($filters['q'] ?? '');
        if ($keyword !== '') {
            $like     = '%' . addcslashes($keyword, '%_\\') . '%';   // treat % and _ as normal characters
            $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }
        // FR-10: every condition in $where is joined with AND below

        $sql = 'SELECT p.place_id, p.name, p.summary, p.distance_km, p.latitude, p.longitude,
                       p.opening_time, p.closing_time, p.closed_days, p.visit_duration_min, p.travel_tips,
                       GROUP_CONCAT(pc.category_id ORDER BY pc.is_primary DESC, pc.category_id) AS category_ids,
                       (SELECT ph.file_path
                          FROM place_photo ph
                         WHERE ph.place_id = p.place_id
                         ORDER BY ph.sort_order, ph.photo_id
                         LIMIT 1) AS thumb_path
                  FROM place p
                  LEFT JOIN place_category pc ON pc.place_id = p.place_id
                 WHERE ' . implode(' AND ', $where) . '
                 GROUP BY p.place_id, p.name, p.summary, p.distance_km, p.latitude, p.longitude,
                          p.opening_time, p.closing_time, p.closed_days, p.visit_duration_min, p.travel_tips
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

    /** FR-12, FR-17: one ACTIVE place with all fields, or null. */
    public static function findById(int $id, bool $includeInactive = false): ?self
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT p.*,
                    (SELECT GROUP_CONCAT(pc.category_id ORDER BY pc.is_primary DESC, pc.category_id)
                       FROM place_category pc
                      WHERE pc.place_id = p.place_id) AS category_ids
               FROM place p
              WHERE p.place_id = ?' . ($includeInactive ? '' : ' AND p.is_active = 1')
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : new self($row);
    }

    /**
     * FR-16: other active places within $radiusKm (straight line), nearest first.
     * Haversine formula; 6371 = Earth's radius in km.
     * @return Place[]
     */
    public function findNearby(float $radiusKm = 5.0, int $limit = 5): array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return [];
        }

        $sql = 'SELECT place_id, name, distance_km, nearby_km
                  FROM (SELECT p.place_id, p.name, p.distance_km,
                               6371 * 2 * ASIN(SQRT(
                                   POW(SIN(RADIANS(p.latitude - :lat1) / 2), 2) +
                                   COS(RADIANS(:lat2)) * COS(RADIANS(p.latitude)) *
                                   POW(SIN(RADIANS(p.longitude - :lng) / 2), 2)
                               )) AS nearby_km
                          FROM place p
                         WHERE p.is_active = 1 AND p.place_id <> :id) AS d
                 WHERE nearby_km <= :radius
                 ORDER BY nearby_km
                 LIMIT :lim';

        $stmt = Database::getConnection()->prepare($sql);
        $stmt->bindValue(':lat1', $this->latitude);
        $stmt->bindValue(':lat2', $this->latitude);
        $stmt->bindValue(':lng', $this->longitude);
        $stmt->bindValue(':id', $this->placeId, PDO::PARAM_INT);
        $stmt->bindValue(':radius', $radiusKm);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn(array $row) => new self($row), $stmt->fetchAll());
    }

    /** Columns the admin form may write. Only these names can ever reach the SQL in save(). */
    private const EDITABLE = [
        'name', 'summary', 'description', 'latitude', 'longitude', 'access_point',
        'opening_time', 'closing_time', 'closed_days', 'entry_fee', 'best_time',
        'visit_duration_min', 'travel_tips', 'facilities', 'info_source', 'distance_km', 'travel_min',
    ];

    /** FR-44: every place, active and deleted, for the admin list (plain rows, not objects) */
    public static function adminList(): array
    {
        return Database::getConnection()->query(
            'SELECT p.place_id, p.name, p.distance_km, p.is_active, p.updated_at,
                    c.name AS primary_category
               FROM place p
               LEFT JOIN place_category pc ON pc.place_id = p.place_id AND pc.is_primary = 1
               LEFT JOIN category c        ON c.category_id = pc.category_id
              ORDER BY p.is_active DESC, p.name'
        )->fetchAll();
    }

    /**
     * FR-45, FR-46, FR-50, FR-53: add a place ($placeId null) or update one, with its categories.
     * @param array<string, mixed> $data  column => value (from PlaceValidator, plus distance_km and travel_min)
     * @param int[] $categoryIds
     * @return int  the place_id
     */
    public static function save(array $data, array $categoryIds, int $primaryId, ?int $placeId = null): int
    {
        $data    = array_intersect_key($data, array_flip(self::EDITABLE));   // never an unknown column
        $columns = array_keys($data);

        $db = Database::getConnection();
        $db->beginTransaction();                     // place and its categories change together
        try {
            if ($placeId === null) {
                $db->prepare('INSERT INTO place (' . implode(', ', $columns) . ')
                              VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')')
                   ->execute(array_values($data));
                $placeId = (int) $db->lastInsertId();
            } else {
                $set = implode(', ', array_map(fn(string $c) => $c . ' = ?', $columns));
                $db->prepare('UPDATE place SET ' . $set . ', updated_at = NOW() WHERE place_id = ?')
                   ->execute([...array_values($data), $placeId]);
                $db->prepare('DELETE FROM place_category WHERE place_id = ?')->execute([$placeId]);
            }

            $link = $db->prepare('INSERT INTO place_category (place_id, category_id, is_primary) VALUES (?, ?, ?)');
            foreach ($categoryIds as $categoryId) {
                $link->execute([$placeId, $categoryId, $categoryId === $primaryId ? 1 : 0]);
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        return $placeId;
    }

    /** FR-51: soft delete. The row stays in the database; visitors no longer see it. */
    public static function deactivate(int $id): bool
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE place SET is_active = 0 WHERE place_id = ? AND is_active = 1'
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }

    /** FR-14 @return PlacePhoto[] */
    public function getPhotos(): array
    {
        return PlacePhoto::findByPlace($this->placeId);
    }

    /** NFR-08: e.g. hasCategory('natural') */
    public function hasCategory(string $name): bool
    {
        foreach ($this->getCategories() as $category) {
            if ($category->name === $name) {
                return true;
            }
        }
        return false;
    }
}