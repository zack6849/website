<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Support\Renderable;

class AdminLogbookController extends Controller
{
    public function index(): Renderable
    {
        return view('admin.logbook.index');
    }
}
