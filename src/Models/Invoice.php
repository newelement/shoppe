<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Newelement\Searchable\SearchableTrait;
use Kyslik\ColumnSortable\Sortable;

class Invoice extends Model
{
    use SoftDeletes, SearchableTrait, Sortable;

}
