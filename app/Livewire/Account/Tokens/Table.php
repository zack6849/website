<?php

namespace App\Livewire\Account\Tokens;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Table extends Component
{

    #[Validate('required')]
    public $newTokenName;

    public function render(): View|Factory|\Illuminate\View\View
    {
        $tokens = auth()->user()->tokens()->get();
        return view('livewire.account.tokens.table', compact('tokens'));
    }

    public function revokeToken($id): void
    {
        auth()->user()->tokens()->where('id', $id)->delete();
    }


    public function createToken(): void
    {
        $this->validate();
        $token = auth()->user()->createToken($this->newTokenName);
        session()->flash('message-success', 'New Token: ' . $token->plainTextToken . " copy this now, it will only be shown once.");
        $this->newTokenName = "";
    }
}
