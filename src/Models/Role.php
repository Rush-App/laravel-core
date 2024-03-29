<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use RushApp\Core\Database\Factories\RoleFactory;

/**
 * Class Role
 *
 * @property int $id
 * @property string $name
 *
 * @property-read Action[]|Collection $actions
 *
 * @package RushApp\Core\Models
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'is_registration_role', 'description'
    ];

    public $timestamps = false;

    public function actions(): BelongsToMany
    {
        return $this->belongsToMany(Action::class, 'role_action')
            ->withPivot([
                'is_owner',
                'excluded_fields',
            ]);
    }

    protected static function newFactory()
    {
        return RoleFactory::new();
    }
}
