<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Newelement\Searchable\SearchableTrait;
use Kyslik\ColumnSortable\Sortable;

class Order extends Model
{
    use SoftDeletes, SearchableTrait, Sortable;

    protected $dates = [
        'created_date',
    ];

    protected $searchable = [
        'columns' => [
            'id' => 1,
            'status' => 7,
            'created_by' => 5,
            'created_at' => 5
        ],
    ];

    public $sortable = [
        'status',
        'created_by',
        'created_at'
    ];

    public $statuses = [
        1 => 'New',
        2 => 'Hold',
        3 => 'Complete',
        4 => 'Refunded',
        86 => 'Canceled'
    ];

    public $linesTotal;

    public function orderLines()
    {
        return $this->hasMany('Newelement\Shoppe\Models\OrderLine');
    }

    public function createdUser()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'created_by');
    }

    public function updatedUser()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'updated_by');
    }

    public function getStatusFormattedAttribute()
    {
        return $this->statuses[ $this->status ];
    }

    public function getItemsTotalAttribute()
    {
        $this->linesTotal = $this->orderLines()->sum(\DB::raw('qty * price'));
        return $this->linesTotal;
    }

    public function shippingAddress()
    {
        return $this->belongsTo('\Newelement\Shoppe\Models\AddressBook', 'address_book_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany('\Newelement\Shoppe\Models\Transaction');
    }

    public function user()
    {
        return $this->belongsTo('\Newelement\Neutrino\Models\User', 'user_id', 'id');
    }

    public function getCreditTotalAttribute()
    {
        return $this->hasMany('Newelement\Shoppe\Models\Transaction')->where('transaction_type', 'credit')->sum('amount');
    }

    public function orderNotes()
    {
        return $this->hasMany('Newelement\Shoppe\Models\OrderNote')->orderBy('created_at', 'desc');
    }

    public function disabled()
    {
        return $this->status === 4 || $this->status === 86? true : false;
    }

}
