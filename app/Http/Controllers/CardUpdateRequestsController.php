<?php

namespace App\Http\Controllers;

use App\CardUpdateRequest;
use App\Http\Resources\CardUpdateRequestResource;
use Illuminate\Http\Request;

class CardUpdateRequestsController extends Controller
{
    public function index()
    {
        return CardUpdateRequestResource::collection(CardUpdateRequest::with("customer")->get());
    }
}
