<?php

namespace App\Http\Controllers;

use App\Repositories\AppPermissionRepository;
use Illuminate\Http\Request;

class AppPermissionController extends Controller
{
    protected AppPermissionRepository $repository;

    public function __construct(AppPermissionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        try {
            $permissions = $this->repository->all();
            return view('permissions.index', compact('permissions'));
        } catch (\Exception $e) {
            // Handle missing permissions table
            return view('permissions.index', ['permissions' => collect()]);
        }
    }
}