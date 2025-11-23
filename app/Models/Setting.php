<?php

declare(strict_types=1);

namespace XetaSuite\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use XetaSuite\Models\Presenters\SettingPresenter;

class Setting extends Model
{
    use HasFactory;
    use SettingPresenter;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'model_type',
        'model_id',
        'text',
        'label',
        'label_info',
        'last_updated_user_id'
    ];
}
