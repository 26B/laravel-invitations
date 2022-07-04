<?php

namespace TwentySixB\LaravelInvitations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Invitation extends Model
{
    use HasFactory;

    /**
     * Indicates that primary key is a string.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates that primary key is not incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data'       => AsArrayObject::class,
        'expires_at' => 'datetime',
    ];

    /**
     * Indicates properties that should not be user set.
     *
     * @var array
     */
    protected $guarded = [
        'created_at',
        'id',
        'modified_at',
    ];

    /**
     * Handles generating UUID when creating a new record.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        self::creating(
            function ($model) {
                $model->id         = (string) Uuid::uuid4();
                $model->code       = (string) Uuid::uuid4();
                $model->expires_at = now()->addWeek();
            }
        );
    }

    /**
     * Get the parent invitable model (event or group).
     */
    public function invitable()
    {
        return $this->morphTo();
    }

    public function author()
    {
        return $this->belongsTo(User::class);
    }
}
