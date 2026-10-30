<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-oriented SIMRS lookup tables with string primary keys.
 */
abstract class AbstractSimrsLookup extends Model
{
    protected $connection = 'dbsimrs';

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
