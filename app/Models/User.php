<?php

namespace App\Models;

use Brick\Math\BigDecimal; // Import BigDecimal
use Illuminate\Database\Eloquent\Casts\Attribute; // Import Attribute
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        // no mass assignment on user model allowed
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the assets owned by the user.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    /**
     * Get the orders owned by the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Interact with the user's balance.
     */
    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => BigDecimal::of($value ?? '0.00000000'),
            set: fn (string|int|float|BigDecimal $value) => BigDecimal::of($value)->toScale(8)->__toString(),
        );
    }
}
