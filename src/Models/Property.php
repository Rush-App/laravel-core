<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Property
 *
 * @property int $id
 * @property bool $is_owner
 */
class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_owner',
    ];

    public $timestamps = false;
}
