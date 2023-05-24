<?php

namespace TwentySixB\LaravelInvitations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use TwentySixB\LaravelInvitations\Exceptions\InvitationExpiredException;

/**
 * Undocumented class
 *
 * @property string $code
 * @property string $author_id
 * @property array $data
 * @property bool $used
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Invitation extends Model
{
    use HasFactory;
	use HasUuids;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
		'data' => AsArrayObject::class,
        'expires_at' => 'datetime',
		'used' => 'bool',
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

	public function use() : self
	{
		if ($this->isExpired() === true) {
			throw new InvitationExpiredException();
		}

		$this->used = true;
		return $this;
	}

	public function expire() : self
	{
		$this->expires_at = now()->subHour(1);
		return $this;
	}

	public function isExpired() : bool
	{
		return $this->expires_at->lt(now());
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
        return $this->belongsTo(config('invitations.models.user'));
    }

	public function scopeExpired(Builder $query): Builder
    {
        return $query
            ->where('expires_at', '>', now());
    }
}
