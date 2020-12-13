<?php

namespace App\Http\Controllers;

use App\ADUpdateRequest;
use App\Http\Resources\ADUpdateRequestResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ADUpdateRequestsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ADUpdateRequestResource::collection(ADUpdateRequest::with('customer')->get());
    }
}
