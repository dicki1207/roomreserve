@extends('layouts.app')

@section('title', 'Dashboard Kepala Laboratorium')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Laboratorium Dikelola</div>
            <div class="stat-value">{{ $myManagedLabs->count() }}</div>
        </div>
        <div class="stat-icon">LAB</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Menunggu Keputusan</div>
            <div class="stat-value">
                {{ $kalabApprovals->where('status', 'MENUNGGU_KALAB')->count() }}
            </div>
        </div>
        <div class="stat-icon">!</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Disetujui</div>
            <div class="stat-value">
                {{ $kalabApprovals->where('status', 'DISETUJUI')->count() }}
            </div>
        </div>
        <div class="stat-icon">✓</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Ditolak</div>
            <div class="stat-value">
                {{ $kalabApprovals->where('status', 'DITOLAK')->count() }}
            </div>
        </div>
        <div class="stat-icon">✕</div>
    </div>
</div>

@if($tab === 'rooms')
    @include('partials.rooms_grid', [
        'title' => 'Status Ruangan GKT & Laboratorium TI',
        'routeUrl' => route('role.kalab'),
        'canBorrow' => false,
    ])
@elseif($tab === 'calendar')
    @include('partials.calendar')
@else
    <div class="grid-main-side">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Permintaan Peminjaman Laboratorium</div>
            </div>

            <div class="card-body">
                @forelse($kalabApprovals as $item)
                    <div class="item-box {{ $item->status === 'MENUNGGU_KALAB' ? 'pending' : '' }}">
                        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:12px;">
                            <div style="flex:1; min-width:250px;">
                                <div style="display:flex; gap:6px; align-items:center; margin-bottom:6px;">
                                    <span class="badge badge-navy">{{ $item->room->room_code }}</span>
                                    <strong>{{ $item->room->room_name }}</strong>
                                    <span class="badge {{ $item->status === 'DISETUJUI' ? 'badge-emerald' : ($item->status === 'DITOLAK' ? 'badge-rose' : 'badge-amber') }}">
                                        {{ str_replace('_', ' ', $item->status) }}
                                    </span>
                                </div>
                                <div style="font-weight:500; margin-bottom:4px;">{{ $item->purpose }}</div>
                                <div style="font-size:12px; color:#64748b;">
                                    Peminjam: {{ $item->requester->full_name }} •
                                    {{ $item->reservation_date }} ({{ $item->start_time }}–{{ $item->end_time }} WIB)
                                </div>

                                @if(!empty($item->approval?->notes))
                                    <div class="reason-box {{ $item->status === 'DITOLAK' ? 'rejected' : 'approved' }}">
                                        <strong>Catatan:</strong> {{ $item->approval->notes }}
                                    </div>
                                @endif
                            </div>

                            @if($item->status === 'MENUNGGU_KALAB')
                                <div style="display:flex; gap:8px;">
                                    <form method="POST" action="{{ route('reservations.decide') }}">
                                        @csrf
                                        <input type="hidden" name="reservation_id" value="{{ $item->id }}">
                                        <input type="hidden" name="decision" value="DISETUJUI">
                                        <input type="hidden" name="notes" value="Disetujui oleh Kepala Laboratorium.">
                                        <button type="submit" class="btn btn-emerald">Setujui</button>
                                    </form>
                                    <button type="button"
                                            onclick="openRejectModal({{ $item->id }}, '#RR-{{ $item->id }}', '{{ $item->room->room_name }}')"
                                            class="btn btn-rose">
                                        Tolak
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p style="color:#64748b;">Belum ada pengajuan peminjaman laboratorium.</p>
                @endforelse
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:18px;">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Laboratorium Saya</div>
                        <div style="font-size:11.5px; color:#64748b;">Status hari ini ({{ $selectedDate }} • {{ $nowTime }} WIB)</div>
                    </div>
                </div>
                <div class="card-body">
                    @foreach($myManagedLabs as $lab)
                        @php
                            $st = $roomStatuses[$lab->id];
                            $approvedSlots = $st['approved_slots'];
                        @endphp
                        <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:10px; background:#f8fafc;">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:6px;">
                                <span class="badge badge-navy">{{ $lab->room_code }}</span>
                                <span class="badge {{ $st['badge_class'] }}">{{ $st['badge_label'] }}</span>
                            </div>
                            <div style="font-weight:600; color:#0f172a;">{{ $lab->room_name }}</div>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">Lantai {{ $lab->floor }} • {{ $lab->capacity }} Unit</div>
                            @if($approvedSlots->isNotEmpty() && $lab->status === 'available')
                                <div style="margin-top:8px; padding:6px 9px; border-radius:6px; background:#fffbeb; border:1px solid #fde68a; font-size:11.5px; color:#92400e;">
                                    @foreach($approvedSlots as $slot)
                                        <div>• Dipinjam {{ $slot->start_time }}–{{ $slot->end_time }} WIB ({{ $slot->requester->full_name }})</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Jadwal Kuliah / Praktikum Lab Saya</div>
                </div>
                <div class="card-body">
                    @php
                        $managedLabIds = $myManagedLabs->pluck('id')->all();
                        $labSchedules = $allSchedules->whereIn('room_id', $managedLabIds);
                    @endphp
                    @forelse($labSchedules as $cs)
                        <div style="padding:12px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:10px; background:#f8fafc;">
                            <div style="display:flex; justify-content:space-between; font-weight:600; color:#0f172a; margin-bottom:4px;">
                                <span class="badge badge-royal">{{ $cs->room->room_code }}</span>
                                <span style="color:#475569; font-size:12px;">{{ $cs->start_time }}–{{ $cs->end_time }}</span>
                            </div>
                            <div style="font-weight:600; color:#1e293b;">{{ $cs->course_name }}</div>
                            @if(!empty($cs->class_name))
                                <div style="margin:3px 0;"><span class="badge badge-navy">Kelas {{ $cs->class_name }}</span></div>
                            @endif
                            <div style="font-size:12px; color:#64748b;">Dosen: {{ $cs->lecturer->full_name }} • {{ $cs->schedule_date }}</div>
                        </div>
                    @empty
                        <p style="color:#64748b; font-size:12.5px;">Belum ada jadwal kuliah/praktikum di laboratorium Anda.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
