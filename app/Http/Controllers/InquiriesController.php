<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class InquiriesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Inquiries');
    }

    public function create(): Response
    {
        return Inertia::render('Inquiries/Create');
    }
}
