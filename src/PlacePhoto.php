<?php
declare(strict_types=1);

final class PlacePhoto
{
    private function __construct(
        public readonly int $photoId,
        public readonly int $placeId,
        public readonly string $filePath,
        public readonly ?string $caption,
        public readonly string $source,      // licence credit (SRS 6.3)
        public readonly int $sortOrder,
    ) {}

    /** FR-14: all photos of one place, in display order. @return PlacePhoto[] */
    public static function findByPlace(int $placeId): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT photo_id, place_id, file_path, caption, source, sort_order
               FROM place_photo
              WHERE place_id = ?
              ORDER BY sort_order, photo_id'
        );
        $stmt->execute([$placeId]);

        return array_map(fn(array $r) => new self(
            (int) $r['photo_id'],
            (int) $r['place_id'],
            $r['file_path'],
            $r['caption'],
            $r['source'],
            (int) $r['sort_order']
        ), $stmt->fetchAll());
    }
}