<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.permissions.index', [
            'permissions' => \Spatie\Permission\Models\Permission::orderBy('name')->get(),
        ]);
    }
}
