<?php
namespace Newelement\Shoppe;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use DB;

class Shoppe
{

	protected $viewLoadingEvents = [];

	protected $models = [
        'Category' => Category::class,
    ];

	public function routes()
	{
	    require __DIR__.'/../routes/shoppe.php';
	}

	public function model($name)
    {
        return app($this->models[Str::studly($name)]);
    }

    public function modelClass($name)
    {
        return $this->models[$name];
    }

    public function useModel($name, $object)
    {
        if (is_string($object)) {
            $object = app($object);
        }
        $class = get_class($object);
        if (isset($this->models[Str::studly($name)]) && !$object instanceof $this->models[Str::studly($name)]) {
            throw new \Exception("[{$class}] must be instance of [{$this->models[Str::studly($name)]}].");
        }
        $this->models[Str::studly($name)] = $class;
        return $this;
    }

	public function view($name, array $parameters = [])
    {
        foreach (Arr::get($this->viewLoadingEvents, $name, []) as $event) {
            $event($name, $parameters);
        }
        return view($name, $parameters);
    }

    public function onLoadingView($name, \Closure $closure)
    {
        if (!isset($this->viewLoadingEvents[$name])) {
            $this->viewLoadingEvents[$name] = [];
        }

        $this->viewLoadingEvents[$name][] = $closure;
    }

	public function getLocales()
    {
        return array_diff(scandir(realpath(__DIR__.'/../publishable/lang')), ['..', '.']);
    }

}
