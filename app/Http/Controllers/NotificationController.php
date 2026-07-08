<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Tampilkan semua notifikasi milik user.
     */
    public function index(): View
    {
        $notifications = auth()->user()->notifications()->paginate(12);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Tandai satu notifikasi dibaca lalu redirect ke URL tujuan.
     */
    public function read(string $id): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');

        return redirect($url);
    }

    /**
     * Tandai semua notifikasi dibaca (single bulk UPDATE).
     */
    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->back()->with('success', 'Semua notifikasi berhasil ditandai sebagai dibaca.');
    }
}
