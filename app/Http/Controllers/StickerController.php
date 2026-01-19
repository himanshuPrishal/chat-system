<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use Illuminate\Http\Request;

class StickerController extends Controller
{
    public function index(Request $request)
    {
        $stickers = Sticker::active()
            ->when($request->pack, function ($query, $pack) {
                $query->pack($pack);
            })
            ->orderBy('pack_name')
            ->orderBy('order')
            ->get()
            ->groupBy('pack_name');

        return response()->json($stickers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'pack_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'file' => 'required|image|max:2048', // 2MB
            'category' => 'nullable|string|max:100',
        ]);

        $path = $request->file('file')->store('stickers', 'public');

        $sticker = Sticker::create([
            'pack_name' => $request->pack_name,
            'name' => $request->name,
            'file_path' => $path,
            'category' => $request->category ?? 'default',
        ]);

        return response()->json($sticker, 201);
    }

    public function packs()
    {
        $packs = Sticker::active()
            ->select('pack_name')
            ->distinct()
            ->get()
            ->pluck('pack_name');

        return response()->json($packs);
    }
}
