<?php

namespace Newelement\Shoppe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Newelement\Searchable\SearchableTrait;
use Kyslik\ColumnSortable\Sortable;
use Auth;

class Product extends Model
{
	use SoftDeletes, SearchableTrait, Sortable;

	protected $searchable = [
        'columns' => [
            'title' => 7,
            'content' => 5,
            'short_desc' => 5,
            'specs' => 5,
        ],
    ];

    public $sortable = [
        'title',
        'status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at'
    ];

	protected $fillable = [
			'title',
			'slug',
			'content',
            'product_type',
            'product_file',
            'role_id',
            'short_content',
            'specs',
            'price',
            'contact_price',
            'contact_avail',
            'sale_price',
            'sku',
            'mfg_part_number',
            'stock',
            'min_stock',
            'weight',
            'width',
            'height',
            'depth',
            'shipping_rate_type',
            'shipping_rate',
			'status',
			'created_at',
			'updated_at',
			'deleted_at',
			'keywords',
			'meta_description',
			'social_image',
			'protected',
			'created_by',
			'updated_by'
		];

	public static function boot()
    {
	    parent::boot();
	    static::creating(function($model){
			if (!app()->runningInConsole()) {
	           $user = Auth::user();
	           $model->created_by = $user->id;
	           $model->updated_by = $user->id;
		   } else{
			   $model->created_by = 1;
   				$model->updated_by = 1;
		   }
	    });

		static::updating(function($model){
	        $user = Auth::user();
	        $model->updated_by = $user->id;
	    });

   	}

	public function createdUser()
	{
		return $this->belongsTo('\Newelement\Neutrino\Models\User', 'created_by');
	}

	public function updatedUser()
	{
		return $this->belongsTo('\Newelement\Neutrino\Models\User', 'updated_by');
	}

	public function featuredImage()
    {
		return $this->hasOne('\Newelement\Neutrino\Models\ObjectMedia', 'object_id', 'id')->where(['object_type' => 'product', 'featured' => 1]);
    }

    public function variationAttributes()
    {
        return $this->hasMany('\Newelement\Shoppe\Models\ProductVariationAttribute');
    }

    public function variations()
    {
        return $this->hasMany('\Newelement\Shoppe\Models\ProductVariation');
    }

	public function url()
	{
		$url = $this->generateUrl();
		return '/'.$url;
	}

	private function generateUrl($parent_id = 0)
	{
		if( $parent_id === 0 ){
			$parent_id = $this->parent_id;
			$paths[] = $this->slug;
		} else {
			$parent = self::where('id', $parent_id)->first();
			$parent_id = $parent->parent_id;
			$paths[] = $parent->slug;
		}

		if ($parent_id > 0){
			$paths[] = $this->generateUrl($parent_id);
		}

		$paths = array_reverse($paths);
		$path = implode('/',$paths);

		return $path;
	}

}
