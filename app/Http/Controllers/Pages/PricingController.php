<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Price;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index()
    {
        $prices = Price::query()->where('is_active', true)->get();
        return view('pages.pricing.index', compact('prices'));
    }
}
