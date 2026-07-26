<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Resources\SantriResource;
use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnakController extends WaliApiController
{
    public function index(Request $request)
    {
        $anak = Auth::user()->anakAsuh()
            ->with(['saldo', 'lembaga', 'kamar'])
            ->orderBy('nama')
            ->get();

        return SantriResource::collection($anak);
    }

    public function show(Santri $santri)
    {
        $this->authorizedSantri($santri);

        $santri->load(['saldo', 'lembaga', 'kamar']);

        return new SantriResource($santri);
    }
}
