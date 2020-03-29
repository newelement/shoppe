<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Newelement\Searchable\SearchableTrait;
use Kyslik\ColumnSortable\Sortable;

class Subscription extends Model
{
    use SoftDeletes, SearchableTrait, Sortable;

    protected $searchable = [
        'columns' => [
            'users.name' => 10,
            'users.email' => 7,
            'subscriptions.name' => 5,
            'subscriptions.stripe_id' => 5,
            'subscriptions.stripe_plan' => 5,
            'customers.customer_id' => 5,
        ],
        'joins' => [
            'users' => ['users.id','subscriptions.user_id'],
            'customers' => ['customers.user_id','users.id'],
        ],
    ];

    public $sortable = [
        'stripe_status',
        'created_at',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'stripe_id',
        'stripe_plan',
        'stripe_status',
        'qty',
        'notes',
        'trial_ends_at',
        'ends_at'
    ];

    public function user()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User');
    }

    public function customer()
    {
        return $this->hasOne('\Newelement\Shoppe\Models\Customer', 'user_id', 'user_id');
    }

}
