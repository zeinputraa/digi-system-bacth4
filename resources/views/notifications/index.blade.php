@extends('layouts.app')

@php $pageTitle = 'Semua Notifikasi'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Notifikasi</h1>
            <p class="page-subtitle">Daftar pemberitahuan sistem, approval peminjaman, reminder jatuh tempo, dan peringatan stok.</p>
        </div>
        @if(auth()->user()->unreadNotifications->isNotEmpty())
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit" class="btn-secondary">
                    Tandai Semua Dibaca
                </button>
            </form>
        @endif
    </div>

    {{-- List Cards --}}
    <div class="card overflow-hidden">
        <div class="divide-y divide-gray-100">
            @forelse($notifications as $notif)
                <a href="{{ route('notifications.read', $notif->id) }}" 
                   class="block p-5 hover:bg-slate-50 transition-colors {{ $notif->unread() ? 'bg-telkom-50/10 border-l-4 border-l-telkom-600' : '' }}">
                    <div class="flex items-start gap-4">
                        {{-- Icon state --}}
                        <div class="shrink-0 mt-1">
                            @if(str_contains($notif->type, 'Overdue') || str_contains($notif->type, 'BelowMinimum'))
                                <span class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-sm">⚠</span>
                            @elseif(str_contains($notif->type, 'Approved'))
                                <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm">✓</span>
                            @else
                                <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm">🛈</span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 leading-normal">{{ $notif->data['message'] ?? 'Notifikasi baru' }}</p>
                            <span class="text-xs text-gray-400 mt-1.5 block">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                        @if($notif->unread())
                            <span class="w-2.5 h-2.5 rounded-full bg-telkom-600 shrink-0 self-center"></span>
                        @endif
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-400 text-sm">
                    Tidak ada notifikasi masuk.
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
