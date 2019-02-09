<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, HasApiTokens, HasUuid, HasRoles;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'remember_token',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
        'uuid',
    ];

    /**
     * @param string $email
     * @return mixed
     */
    public static function findByEmail(string $email)
    {
        return static::where('email', $email)->firstOrFail();
    }

    /**
     * @param string $email
     * @return mixed
     */
    public static function findByPhoneNumber(string $phone)
    {
        return static::where('phone_number', $phone)->firstOrFail();
    }

    /**
     * @param string $password
     * @return bool
     */
    public function hasPassword(string $password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     * @return string
     */
    public function generateToken()
    {
        return $this->createToken('Laravel Password Grant Client')->accessToken;
    }

    /**
     * @return void
     */
    public function removeToken($id)
    {
        $token = $this->tokens->find($id);

        $token->revoke();
    }

    /*
     * A User can have many ownership requests
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessOwnershipRequests()
    {
        return $this->hasMany(OwnershipRequest::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(BusinessReview::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return
        $this->belongsToMany(Category::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function businesses()
    {
        return $this->hasMany(Business::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function businessPosts()
    {
        return $this->hasMany(BusinessPost::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function bookmarks()
    {
        return $this->hasMany('App\Model\Business', 'user_bookmarks', 'user_id', 'business_id')->withPivot('status');
    }

    /**
     * Send account verificaton function
     */
    public function sendVerification()
    {
        if (isset($this->email)) {
//            $this->sendEmailVerificationNotification();
            $this->notify(new VerifyEmailNotification());
        } else {
            $pin = \HelperServiceProvider::generatePin(5);

            $this->fill([
                'verification_code' => Hash::make($pin),
            ])->save();

            $application = config('app.name');
            \Twilio::message($this->phone_number, "Thanks for registering to {$application}. Your verification pin is {$pin}. Please sign in to verify your account.");
        }
    }

}
