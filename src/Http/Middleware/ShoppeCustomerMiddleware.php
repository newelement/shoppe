<?php
namespace Newelement\Shoppe\Http\Middleware;
use Closure;

class ShoppeCustomerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!app('ShoppeAuth')->guest()) {
            $user = app('ShoppeAuth')->user();
            return $user->hasRole('customer') || $user->hasRole('admin') || $user->hasRole('editor') ? $next($request) : redirect('/');
        }
        $urlLogin = route('login');
        return redirect()->guest($urlLogin);
    }
}
