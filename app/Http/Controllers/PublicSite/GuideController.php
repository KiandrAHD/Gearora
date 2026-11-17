<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(): View
    {
        return view('guides.index');
    }
}
