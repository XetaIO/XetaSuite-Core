<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Xetaio\Counts\Concerns\HasCounts;
use XetaSuite\Models\Concerns\SiteScoped;
use XetaSuite\Models\Presenters\CalendarEventPresenter;

class CalendarEvent extends Model
{
    use CalendarEventPresenter;
    use HasCounts;
    use HasFactory;
    use SiteScoped;

    protected $fillable = [
        'site_id',
        'event_category_id',
        'created_by_id',
        'created_by_name',
        'title',
        'description',
        'color',
        'start_at',
        'end_at',
        'all_day',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * The relations to be counted.
     */
    protected static array $countsConfig = [
        'eventCategory' => 'calendar_event_count',
    ];

    /**
     *  Get the site where belongs the calendar event.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     *  Get the event category where belongs the calendar event.
     */
    public function eventCategory(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * Get the user who created the calendar event.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
