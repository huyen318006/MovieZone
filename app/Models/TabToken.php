<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 */
class TabToken extends Model
{
    // tên table
    protected $table = 'tab_tokens';
    protected $fillable = [
        'user_id',
        'token',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Quan hệ belongsTo với User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tạo một token mới cho user.
     * Token có hiệu lực 24 giờ (có thể tùy chỉnh).
     */
    public static function createForUser(User $user, int $hours = 24): self
    {
        return static::create([
            'user_id'    => $user->id,
            'token'      => Str::random(60),
            'expires_at' => Carbon::now()->addHours($hours),
        ]);
    }

    /**
     * Kiểm tra token còn hiệu lực không.
     */

    // Quan hệ: token này thuộc về user nào

    public function isValid(): bool
    {
        return $this->expires_at === null || Carbon::now()->lessThan($this->expires_at);
    }

    /**
     * Cập nhật thời gian sử dụng cuối.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => Carbon::now()]);
    }
}

