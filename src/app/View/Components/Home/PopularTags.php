<?php

namespace App\View\Components\Home;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PopularTags extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public Collection $tags)
    {
        $this->tags = $tags instanceof Collection ? $tags : collect($tags ?? []);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.home.popular-tags');
    }
}
