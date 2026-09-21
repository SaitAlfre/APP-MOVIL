<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DesignSystemController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.design-system.index');
    }
}
