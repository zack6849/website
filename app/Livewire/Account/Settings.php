<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Settings extends Component
{
    public function render(): Factory|View|\Illuminate\View\View
    {
        $tokens = auth()->user()->tokens()->get();
        return view('livewire.account.settings', ['tokens' => $tokens]);
    }
}
