<?php

namespace App\View\Components\Home;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class allCategory extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public Collection $categories)
    {
        $this->categories = $categories instanceof Collection ? $categories : collect($categories ?? []);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.home.all-category');
    }
}
