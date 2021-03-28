<?php

namespace RushApp\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RushApp\Core\Database\Factories\LanguageFactory;

/**
 * Class Language
 *
 * @property int $id
 * @property string $name
 *
 * @package RushApp\Core\Models
 */
class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    protected static function newFactory()
    {
        return LanguageFactory::new();
    }
}
