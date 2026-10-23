<?php
declare(strict_types=1);

/**
 * Checks the admin place form (FR-45, FR-46, FR-48, FR-49).
 * The 25 km road check (FR-47) needs OSRM, so admin/place-form.php does it after this.
 */
final class PlaceValidator
{
    public const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun', 'Poya'];

    /** @var array<string, string>  field => message (FR-48: every field in error is named) */
    public array $errors = [];
    /** @var array<string, mixed>  cleaned values, ready for Place::save() */
    public array $data = [];
    /** @var array<string, mixed>  what the admin typed, to show again if there are errors */
    public array $values = [];
    /** @var int[] */
    public array $categoryIds = [];
    public int $primaryId = 0;

    /** @param array<int, Category> $allCategories */
    public function __construct(private array $post, array $allCategories)
    {
        // Required text (FR-48)
        $this->data['name']        = $this->text('name', 'Name', 120, true);
        $this->data['description'] = $this->text('description', 'Description', 5000, true);

        // Optional text: empty becomes NULL in the database
        $summary                    = $this->text('summary', 'Short summary', 200);
        $this->data['access_point'] = $this->text('access_point', 'Main entrance', 150);
        $this->data['entry_fee']    = $this->text('entry_fee', 'Entry fee', 120);
        $this->data['best_time']    = $this->text('best_time', 'Best time to visit', 120);
        $this->data['travel_tips']  = $this->text('travel_tips', 'Travel tips', 5000);
        $this->data['facilities']   = $this->text('facilities', 'Facilities', 255);
        $this->data['info_source']  = $this->text('info_source', 'Information source', 255);

        // The list page needs a summary: if none is given, use the start of the description
        $description = (string) $this->data['description'];
        $this->data['summary'] = $summary ?? (mb_strlen($description) > 200
            ? rtrim(mb_substr($description, 0, 197)) . '...'
            : $description);

        if ($this->data['info_source'] !== null && !preg_match('#^https?://#i', $this->data['info_source'])) {
            $this->errors['info_source'] = 'Information source must be a web address starting with http:// or https://.';
        }

        // Coordinates (FR-48 required, FR-49 range)
        $this->data['latitude']  = $this->coordinate('latitude', 'Latitude', 90);
        $this->data['longitude'] = $this->coordinate('longitude', 'Longitude', 180);

        // Opening hours: both or neither, closing after opening
        $open  = $this->time('opening_time', 'Opening time');
        $close = $this->time('closing_time', 'Closing time');
        if (($open === null) !== ($close === null)) {
            $this->errors['closing_time'] = 'Give both an opening and a closing time, or leave both empty.';
        } elseif ($open !== null && $close <= $open) {
            $this->errors['closing_time'] = 'Closing time must be later than opening time.';
        }
        $this->data['opening_time'] = $open;
        $this->data['closing_time'] = $close;

        // Closed days: only the 8 allowed values, in a fixed order
        $days = array_values(array_intersect(self::DAYS, $this->list('closed_days')));
        $this->values['closed_days'] = $days;
        $this->data['closed_days']   = implode(',', $days);

        // Suggested visit duration (minutes)
        $duration = $this->text('visit_duration_min', 'Suggested visit duration', 4);
        if ($duration !== null && (!ctype_digit($duration) || (int) $duration < 5 || (int) $duration > 720)) {
            $this->errors['visit_duration_min'] = 'Suggested visit duration must be a whole number of minutes from 5 to 720.';
        }
        $this->data['visit_duration_min'] = $duration === null ? null : (int) $duration;

        // Categories (FR-46): at least one of the seven; the primary one sets the map marker colour
        $this->categoryIds = array_values(array_unique(array_filter(
            array_map('intval', $this->list('categories')),
            fn(int $id) => isset($allCategories[$id])
        )));
        $this->values['categories'] = $this->categoryIds;
        if ($this->categoryIds === []) {
            $this->errors['categories'] = 'Choose at least one category.';
        }
        $primary = (int) ($post['primary'] ?? 0);
        $this->primaryId = in_array($primary, $this->categoryIds, true) ? $primary : ($this->categoryIds[0] ?? 0);
        $this->values['primary'] = $this->primaryId;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** Trimmed text or null; adds an error when required and empty, or too long */
    private function text(string $key, string $label, int $max, bool $required = false): ?string
    {
        $value = is_string($this->post[$key] ?? null) ? trim($this->post[$key]) : '';
        $this->values[$key] = $value;
        if ($value === '') {
            if ($required) {
                $this->errors[$key] = $label . ' is required.';
            }
            return null;
        }
        if (mb_strlen($value) > $max) {
            $this->errors[$key] = $label . ' must be at most ' . $max . ' characters.';
        }
        return $value;
    }

    /** FR-48, FR-49: a number between -$limit and $limit, rounded to 6 decimal places */
    private function coordinate(string $key, string $label, int $limit): ?float
    {
        $value = $this->text($key, $label, 20, true);
        if ($value === null) {
            return null;
        }
        if (!is_numeric($value) || abs((float) $value) > $limit) {
            $this->errors[$key] = $label . ' must be a number between -' . $limit . ' and ' . $limit . '.';
            return null;
        }
        return round((float) $value, 6);
    }

    /** "HH:MM" from a time box, or null */
    private function time(string $key, string $label): ?string
    {
        $value = $this->text($key, $label, 8);
        if ($value === null) {
            return null;
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value)) {
            $this->errors[$key] = $label . ' must be a time like 08:30.';
            return null;
        }
        return substr($value, 0, 5);
    }

    /** @return string[]  a list of checkbox values from the form (anything else is ignored) */
    private function list(string $key): array
    {
        $raw = $this->post[$key] ?? [];
        return is_array($raw) ? array_map('strval', array_filter($raw, 'is_string')) : [];
    }
}
