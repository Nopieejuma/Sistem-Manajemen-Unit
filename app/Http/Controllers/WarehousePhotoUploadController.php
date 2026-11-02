<?php

namespace App\Http\Controllers;

use App\Models\WarehousePhotoUpload;
use App\Services\WarehousePhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehousePhotoUploadController extends Controller
{
    public function store(Request $request, WarehousePhotoStorage $photos): JsonResponse
    {
        abort_unless($photos->enabled(), 404);
        $data = $request->validate([
            'warehouse_id' => ['nullable', 'uuid', 'exists:gudang,id'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*.mime' => ['required', 'in:image/jpeg,image/png,image/webp'],
            'files.*.size' => ['required', 'integer', 'min:1', 'max:2097152'],
        ]);

        return response()->json(['uploads' => $photos->issue($request->user(), $data['warehouse_id'] ?? null, $data['files'])])
            ->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request, WarehousePhotoUpload $upload, WarehousePhotoStorage $photos): JsonResponse
    {
        abort_unless($photos->enabled(), 404);
        abort_unless((string) $upload->admin_id === (string) $request->user()->getKey(), 403);
        DB::transaction(function () use ($upload): void {
            $record = WarehousePhotoUpload::query()->whereKey($upload->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending', 409);
            // Keep the signed URL's replay window tracked before deleting staged data.
            $record->update(['status' => 'canceled', 'expires_at' => now()->addMinutes(6)]);
        });

        return response()->json(['message' => 'Upload dibatalkan dan dijadwalkan untuk dibersihkan.']);
    }
}
