<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RushApp\Core\Database\Factories\ActionFactory;

/**
 * Class Action
 *
 * @property int $id
 * @property string $name
 */
class Action extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    protected static function newFactory()
    {
        return ActionFactory::new();
    }
}
