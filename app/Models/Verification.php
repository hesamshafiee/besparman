<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'code', 'expire_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param $query
     * @param int $code
     * @param User $user
     * @return bool
     */
    public function scopeVerifyCode($query, int $code, User $user): bool
    {
        if ($user->verification()->whereCode($code)->where('expire_at', '>', now())->first()) {
            if (!$user->mobile_verified_at) {
                $user->mobile_verified_at = now();
                $user->save();
            }
            return true;
        }

        return false;
    }

    /**
     * @param $query
     * @param User $user
     * @return int
     */
    public function scopeGenerateCode($query, User $user): int
    {
        if ($code = $this->getAliveCodeForUser($user)) {
            return $code->code;
        } else {
            do {
                $code = mt_rand(100000, 999999);
            } while($this->checkCodeIsUnique($user, $code));
        }

        $user->verification()->create([
            'code' => $code,
            'expire_at' => now()->addMinutes((int) env('VERIFICATION_CODE_EXPIRE_TIME_IN_MINUTES', 2))
        ]);

        return $code;
    }

    /**
     * @param User $user
     * @param int $code
     * @return bool
     */
    private function checkCodeIsUnique(User $user, int $code): bool
    {
        return !! $user->verification()->whereCode($code)->first();
    }

    /**
     * @param User $user
     * @return object|null
     */
    private function getAliveCodeForUser(User $user): object|null
    {
        return $user->verification()->where('expire_at', '>', now())->first();
    }
}
