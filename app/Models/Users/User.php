<?php

namespace App\Models\Users;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\Auth\PasswordHelper;
use App\Models\Conversation;
use App\Models\MessageFeedback as AppMessageFeedback;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use InteractsWithMedia;
    use HasRoles;

    protected $fillable = [
        'username',
        'last_name',
        'first_name',
        'email',
        'phone_number',
        'password',
        'last_password_change',
        'is_active',
        'last_login',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'last_password_change' => 'datetime',
            'last_login' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function setting(): HasOne
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(AppMessageFeedback::class);
    }


    protected function getDefaultGuardName(): string
    {
        return 'api';
    }

    /**
     * Find user for Passport authentication
     */
    public function findForPassport($username)
    {
        return $this->where('email', $username)
            ->orWhere('username', $username)
            ->first();
    }

    /**
     * Validate password for Passport grant
     */
    public function validateForPassportPasswordGrant($password)
    {
        if (!$this->is_active) {
            return false;
        }

        return PasswordHelper::verify($password, $this->password);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'])
            ->acceptsFile(fn(File $file) => $file->size <= config('support.maximum_profile_image_size') * 1024);
    }

    protected function profileImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getFirstMediaUrl('profile_image') ?: null,
        );
    }

    protected function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value,
            set: function (?string $value) {
                if (empty($value)) {
                    return null;
                }

                try {
                    $phone = new PhoneNumber($value);
                    return $phone->formatE164();
                } catch (\Exception $e) {
                    return $value;
                }
            }
        );
    }
}
