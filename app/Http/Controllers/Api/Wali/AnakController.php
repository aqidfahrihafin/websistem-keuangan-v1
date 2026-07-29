<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Resources\SantriResource;
use App\Models\Santri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

class AnakController extends WaliApiController
{
    public function index(Request $request): JsonResponse
    {
        try {
            $anak = Auth::user()->anakAsuh()
                ->with(['saldo', 'lembaga', 'kamar'])
                ->orderBy('nama')
                ->get();

            return response()->json(SantriResource::collection($anak)->response()->getData(true));
        } catch (Throwable $exception) {
            if ($exception instanceof HttpExceptionInterface || $exception instanceof HttpResponseException) {
                throw $exception;
            }

            return response()->json([
                'message' => 'Gagal memuat data anak.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function show(Santri $santri): JsonResponse
    {
        try {
            $this->authorizedSantri($santri);

            $santri->load(['saldo', 'lembaga', 'kamar']);

            return response()->json((new SantriResource($santri))->response()->getData(true));
        } catch (Throwable $exception) {
            if ($exception instanceof HttpExceptionInterface || $exception instanceof HttpResponseException) {
                throw $exception;
            }

            return response()->json([
                'message' => 'Gagal memuat data anak.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }
}
