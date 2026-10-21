<?php
declare(strict_types=1);

/** A saved day plan (FR-35): stored in visit_plan + visit_plan_item and found again by its reference code. */
final class VisitPlan
{
    public const MAX_STOPS = 20;
    private const CODE_CHARS  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O or 1/I, so codes are easy to read out
    private const CODE_LENGTH = 8;
    private const KEEP_DAYS   = 90;                                  // SRS §6.1.2: saved plans are kept for 90 days

    public readonly string $refCode;
    public readonly string $visitDate;   // "2026-10-25"
    public readonly string $startTime;   // "08:00"
    /** @var int[]  place IDs in plan order */
    public readonly array $stops;

    private function __construct(string $refCode, string $visitDate, string $startTime, array $stops)
    {
        $this->refCode   = $refCode;
        $this->visitDate = $visitDate;
        $this->startTime = $startTime;
        $this->stops     = $stops;
    }

    /**
     * Check and store a plan, and give it a new reference code.
     * @param array $stops  place IDs in order
     * @throws InvalidArgumentException when the plan is not valid (message is safe to show)
     */
    public static function create(array $stops, string $startTime, string $visitDate): self
    {
        $stops = array_values($stops);
        self::validate($stops, $startTime, $visitDate);

        $db = Database::getConnection();
        $db->beginTransaction();                 // plan and items are saved together, or not at all
        try {
            // Retention: remove plans older than 90 days (their items go too: ON DELETE CASCADE)
            $db->exec('DELETE FROM visit_plan WHERE created_at < NOW() - INTERVAL ' . self::KEEP_DAYS . ' DAY');

            $code   = self::insertWithNewCode($db, $visitDate, $startTime);
            $planId = (int) $db->lastInsertId();

            $item = $db->prepare('INSERT INTO visit_plan_item (plan_id, sequence_no, place_id) VALUES (?, ?, ?)');
            foreach ($stops as $i => $placeId) {
                $item->execute([$planId, $i + 1, $placeId]);
            }
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
        return new self($code, $visitDate, $startTime, $stops);
    }

    /** FR-35: the saved plan with this code, or null. Lower-case codes are accepted. */
    public static function findByCode(string $code): ?self
    {
        $code = strtoupper(trim($code));
        if (!self::isValidCode($code)) {
            return null;
        }
        $db   = Database::getConnection();
        $stmt = $db->prepare('SELECT plan_id, ref_code, visit_date, start_time FROM visit_plan WHERE ref_code = ?');
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $items = $db->prepare('SELECT place_id FROM visit_plan_item WHERE plan_id = ? ORDER BY sequence_no');
        $items->execute([$row['plan_id']]);

        return new self(
            $row['ref_code'],
            $row['visit_date'],
            substr($row['start_time'], 0, 5),            // "08:00:00" -> "08:00"
            array_map('intval', $items->fetchAll(PDO::FETCH_COLUMN))
        );
    }

    public static function isValidCode(string $code): bool
    {
        return preg_match('/^[' . self::CODE_CHARS . ']{' . self::CODE_LENGTH . '}$/', $code) === 1;
    }

    /** @throws InvalidArgumentException */
    private static function validate(array $stops, string $startTime, string $visitDate): void
    {
        if ($stops === [] || count($stops) > self::MAX_STOPS) {
            throw new InvalidArgumentException('A plan needs 1 to ' . self::MAX_STOPS . ' stops.');
        }
        foreach ($stops as $id) {
            if (!is_int($id) || $id < 1) {
                throw new InvalidArgumentException('Stops must be place IDs.');
            }
        }
        if (count($stops) !== count(array_unique($stops))) {
            throw new InvalidArgumentException('A place appears more than once.');
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $startTime)) {
            throw new InvalidArgumentException('The start time must look like 08:00.');
        }
        $date = DateTime::createFromFormat('!Y-m-d', $visitDate);
        if ($date === false || $date->format('Y-m-d') !== $visitDate) {
            throw new InvalidArgumentException('The visit date must look like 2026-10-25.');
        }
        if ($visitDate < date('Y-m-d')) {
            throw new InvalidArgumentException('The visit date cannot be in the past.');
        }

        // Every stop must be an active place
        $marks = implode(',', array_fill(0, count($stops), '?'));
        $stmt  = Database::getConnection()->prepare(
            "SELECT COUNT(*) FROM place WHERE is_active = 1 AND place_id IN ($marks)"
        );
        $stmt->execute($stops);
        if ((int) $stmt->fetchColumn() !== count($stops)) {
            throw new InvalidArgumentException('A place in the plan is not available.');
        }
    }

    /** Insert the plan row with a random code; on the rare duplicate code, try another. */
    private static function insertWithNewCode(PDO $db, string $visitDate, string $startTime): string
    {
        $stmt = $db->prepare('INSERT INTO visit_plan (ref_code, visit_date, start_time) VALUES (?, ?, ?)');
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $code = self::newCode();
            try {
                $stmt->execute([$code, $visitDate, $startTime . ':00']);
                return $code;
            } catch (PDOException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062) {   // 1062 = duplicate key; anything else is a real error
                    throw $e;
                }
            }
        }
        throw new RuntimeException('Could not create a unique reference code.');
    }

    /** e.g. "K7M3QX9P", from a cryptographically secure random generator */
    private static function newCode(): string
    {
        $code = '';
        $last = strlen(self::CODE_CHARS) - 1;
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_CHARS[random_int(0, $last)];
        }
        return $code;
    }
}
