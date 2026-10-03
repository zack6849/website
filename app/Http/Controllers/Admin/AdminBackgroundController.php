<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Background;
use Illuminate\Contracts\Support\Renderable;

class AdminBackgroundController extends Controller
{
    public function index(): Renderable
    {
        return view('admin.backgrounds.index');
    }

    public function create(): Renderable
    {
        return view('admin.backgrounds.form', ['backgroundId' => null]);
    }

    public function edit(Background $background): Renderable
    {
        return view('admin.backgrounds.form', ['backgroundId' => $background->id]);
    }
}
