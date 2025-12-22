<?php

namespace App\Models;

use App\Events\CreateToken;
use App\Traits\LogsActivityWithRequest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authentication;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authentication
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;
    use LogsActivityWithRequest;


    const MOBILE_ADMIN = '888888888888';

    const TYPE_PICBOOM = 'esaj';
    const TYPE_ORIDINARY = 'ordinary';
    const TYPE_PANEL = 'panel';
    const TYPE_WEBSERVICE = 'webservice';
    const TYPE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mobile',
        'email',
        'password',
        'name',
        'type',
        'two_step',
        'private',
        'profile_confirm'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * @return HasMany
     */
    public function verification() : HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /**
     * @return JsonResponse
     */
    public function generateToken(): JsonResponse
    {
        $accessToken = $this->createToken(User::TYPE_PANEL, ['*'], now()->addHour());
        $refreshToken = $this->createToken('refreshToken', ['refresh'], now()->addHours(3));

        $tokenModel = $accessToken->accessToken;

        CreateToken::dispatch(
            $this,
            $tokenModel,
            request()->ip(),
            request()->header('User-Agent')
        );

        return response()->json([
            'status'        => true,
            'message'       => 'User Logged In Successfully',
            'access_token'  => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'balance'       => $this->wallet->value ?? 0,
            'p'             => !empty($this->password),
            'auth'          => config('general.auth'),
            'otp'           => config('general.otp'),
            'roles'         => $this->getRoleNames(),
            'permissions'   => $this->getAllPermissions(),
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function spaLogin() : JsonResponse
    {
        Auth::guard('web')->login($this);
        session()->regenerate();

        return response()->json([
            'status' => true,
            'message' => 'User Logged In Successfully',
            'balance' => $this->wallet->value,
            'p' => !empty($this->password),
            'auth' => config('general.auth'),
            'otp' => config('general.otp'),
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()
        ]);
    }

    /**
     * @param string $password
     * @return bool
     */
    public function passwordCheck(string $password) : bool
    {
        if (!empty($this->password) && Hash::check($password, $this->password)) {
            return true;
        }

        return false;
    }

    /**
     * @return MorphToMany
     */
    public function discounts() : MorphToMany
    {
        return $this->morphToMany(Discount::class, 'discountable');
    }

    /**
     * @return HasOne
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }



    /**
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * @return HasMany
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /**
     * @return HasMany
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'user_id');
    }

    /**
     * @param User|null $user
     * @return string
     */
    public static function nameOrMobile(User $user = null): string
    {
        if ($user) {
            return empty($user->name) ? optional($user)->mobile : optional($user)->name;
        } else {
            $user = Auth::user();
            return empty($user->name) ? $user->mobile : $user->name;
        }
    }

    /**
     * @param string $mobile
     * @return Authenticatable
     */
    public static function getLoggedInUserOrGetFromGivenMobile(string $mobile): Authenticatable
    {
        if (Auth::user()) {
            return Auth::user();
        }

        $user = User::where('mobile', $mobile)->first();

        if ($user) {
            return $user;
        }

        $newUser = new User();
        $newUser->mobile = $mobile;

        if ($newUser->save()) {
            return User::findOrFail($newUser->id);
        }

        abort(404);
    }

    /**
     * @return string
     */
    public function staredNameOrMobile(): string
    {
        return empty($this->name) ? substr_replace($this->mobile, '****', 5, 4) : $this->name;
    }

    /**
     * @return string
     */
    public function confirmedBy(): string
    {
        return $this->name . ' / ' . $this->mobile . ' / ' . 'userId: ' . $this->id . ' / ' . now();
    }

    /**
     * @return BelongsTo
     */
    public function profitGroup()
    {
        return $this->belongsTo(ProfitGroup::class);
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasOne
     */
    public function settings() : HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * @return HasMany
     */
    public function reportDailyBalances(): HasMany
    {
        return $this->hasMany(ReportDailyBalance::class);
    }

    /**
     * @return BelongsTo
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * @return HasOne
     */
    public function wallets(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }



    public function telegramAccounts()
    {
        return $this->hasMany(UserTelegramAccount::class);
    }

    public function telegramIds()
    {
        return $this->telegramAccounts()->pluck('telegram_id')->toArray();
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    /**
     * @return bool
     */
    public function isPanel(): bool
    {
        return $this->type === self::TYPE_PANEL;
    }

    /**
     * @return bool
     */
    public function isWebservice(): bool
    {
        return $this->type === self::TYPE_WEBSERVICE;
    }

    /**
     * @return bool
     */
    public function isEsaj(): bool
    {
        return $this->type === self::TYPE_PICBOOM;
    }

    /**
     * @return bool
     */
    public function isOrdinary(): bool
    {
        return $this->type === self::TYPE_ORIDINARY;
    }

    /**
     * @return bool
     */
    public function isPanelOrWebservice(): bool
    {
        return ($this->isPanel() || $this->isWebservice());
    }

    /**
     * @return bool
     */
    public function isAdminOrEsaj(): bool
    {
        return ($this->isAdmin() || $this->isEsaj());
    }
}
