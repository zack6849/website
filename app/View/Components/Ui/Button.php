<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    /** Maps a button type to a [color, weight] pair. */
    public const array BUTTON_TYPES = [
        'info' => ['color' => 'sky', 'weight' => 500],
        'success' => ['color' => 'green', 'weight' => 600],
        'warning' => ['color' => 'amber', 'weight' => 500],
        'danger' => ['color' => 'red', 'weight' => 600],
        'secondary' => ['color' => 'gray', 'weight' => 600],
    ];

    public string $color;

    public int $weight;

    /**
     * An explicit color / weight overrides the one implied by the type.
     */
    public function __construct(
        public string $type = 'info',
        ?string       $color = null,
        ?int          $weight = null,
    )
    {
        $defaults = self::BUTTON_TYPES[$this->type];
        $this->color = $color ?? $defaults['color'];
        $this->weight = $weight ?? $defaults['weight'];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.ui.button');
    }

    /**
     * Classes are built dynamically; the possible combinations are safelisted
     * with @source inline() in resources/css/app.css.
     */
    public function colorClasses(): string
    {
        $c = $this->color;
        $w = $this->weight;

        return sprintf(
            'bg-%1$s-%2$d text-white hover:bg-%1$s-%3$d focus:border-%1$s-%4$d active:bg-%1$s-%4$d',
            $c, $w, $w - 100, $w + 100
        );
    }
}
