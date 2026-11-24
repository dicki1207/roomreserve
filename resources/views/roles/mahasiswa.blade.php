@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa')

@section('content')
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-label">Total Pengajuan</div>
            <div class="stat-value">{{ $myReservations->count() }}</div>
        </div>
        <div class="stat-icon">∑</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Menunggu Persetujuan</div>
            <div class="stat-value">
                {{ $myReservations->filter(fn($r) => str_starts_with($r->status, 'MENUNGGU'))->count() }}
            </div>
        </div>
        <div class="stat-icon">⏳</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Disetujui</div>
            <div class="stat-value">
                {{ $myReservations->where('status', 'DISETUJUI')->count() }}
            </div>
        </div>
        <div class="stat-icon">✓</div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-label">Ditolak</div>
            <div class="stat-value">
                {{ $myReservations->where('status', 'DITOLAK')->count() }}
            </div>
        </div>
        <div class="stat-icon">✕</div>
    </div>
</div>

@if($tab === 'rooms')
    @include('partials.rooms_grid', [
        'title' => 'Daftar Ruangan GKT & Laboratorium TI',
        'routeUrl' => route('role.mahasiswa'),
    ])
@elseif($tab === 'calendar')
    @include('partials.calendar')
@else
    <div class="grid-main-side">
        <div class="card">
            <div class="card-header">
                <div class="card-title">Riwayat Pengajuan Peminjaman</div>
                <button type="button" onclick="openReserveModal()" class="btn btn-navy" style="font-size:12px; padding:6px 12px;">+ Buat Pengajuan</button>
            </div>
            <div class="card-body">
                @forelse($myReservations as $res)
                    <div class="item-box">
                        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px; margin-bottom:6px;">
                            <div style="display:flex; gap:6px; align-items:center;">
                                <span class="badge badge-navy">#RR-{{ $res->id }}</span>
                                <span class="badge badge-royal">{{ $res->room->room_code }}</span>
                                <strong style="color:#0f172a;">{{ $res->room->room_name }}</strong>
                            </div>
                            @if($res->status === 'DISETUJUI')
                                <span class="badge badge-emerald">Disetujui</span>
                            @elseif($res->status === 'DITOLAK')
                                <span class="badge badge-rose">Ditolak</span>
                            @else
                                <span class="badge badge-amber">{{ str_replace('_', ' ', $res->status) }}</span>
                            @endif
                        </div>

                        <div style="font-weight:500; color:#1e293b; margin-bottom:4px;">{{ $res->purpose }}</div>
                        <div style="font-size:12px; color:#64748b; margin-bottom:6px;">
                            {{ $res->reservation_date }} ({{ $res->start_time }}–{{ $res->end_time }} WIB) •
                            Tujuan: <strong>{{ $res->approval?->approver?->full_name ?? $res->approval?->approver_type ?? '-' }}</strong>
                        </div>

                        @if($res->status === 'DITOLAK' && !empty($res->approval?->notes))
                            <div class="reason-box rejected">
                                <strong>Alasan Penolakan:</strong> {{ $res->approval->notes }}
                            </div>
                        @elseif($res->status === 'DISETUJUI' && !empty($res->approval?->notes))
                            <div class="reason-box approved">
                                <strong>Catatan:</strong> {{ $res->approval->notes }}
                            </div>
                        @endif

                        {{-- Fitur CRUD: Update & Delete hanya jika masih berstatus MENUNGGU --}}
                        @if(str_starts_with($res->status, 'MENUNGGU'))
                            <div style="display:flex; gap:8px; margin-top:10px; padding-top:8px; border-top:1px dashed #e2e8f0;">
                                <button type="button"
                                        onclick="openEditReserveModal({{ $res->id }}, {{ $res->room_id }}, '{{ $res->reservation_date }}', '{{ $res->start_time }}', '{{ $res->end_time }}', '{{ addslashes($res->purpose) }}')"
                                        class="btn btn-slate" style="padding:4px 10px; font-size:11.5px;">
                                    Edit Pengajuan
                                </button>
                                <form method="POST" action="{{ route('mahasiswa.reservations.destroy', $res->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pengajuan peminjaman ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-rose" style="padding:4px 10px; font-size:11.5px;">
                                        Batalkan Pengajuan
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="color:#64748b;">Belum ada pengajuan peminjaman ruangan.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="flex-wrap:wrap; gap:8px;">
                <div>
                    <div class="card-title">Jadwal Kuliah GKT &amp; Lab</div>
                    <div style="font-size:11.5px; color:#64748b; margin-top:1px;">
                        Tanggal <strong>{{ $selectedDate }}</strong> @if($isToday) (Hari Ini) @endif
                    </div>
                </div>
                <form method="GET" action="{{ route('role.mahasiswa') }}" style="display:flex; gap:5px; align-items:center;">
                    <input type="hidden" name="tab" value="dashboard">
                    <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control" style="padding:4px 8px; font-size:11.5px; width:auto;">
                    @if(!$isToday)
                        <a href="{{ route('role.mahasiswa') }}" class="btn btn-slate" style="padding:4px 8px; font-size:11px;">Hari Ini</a>
                    @endif
                </form>
            </div>
            <div class="card-body">
                @forelse($dailySchedules as $cs)
                    <div style="padding:11px 12px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:8px; font-size:12.5px; background:#f8fafc;">
                        <div style="display:flex; justify-content:space-between; font-weight:600; color:#0f172a; margin-bottom:2px;">
                            <span>{{ $cs->room->room_code }}</span>
                            <span style="color:#475569;">{{ $cs->start_time }}–{{ $cs->end_time }} WIB</span>
                        </div>
                        <div style="font-weight:500; color:#1e293b;">
                            {{ $cs->course_name }}@if(!empty($cs->class_name)) ({{ $cs->class_name }})@endif
                        </div>
                        <div style="color:#64748b; font-size:11.5px;">{{ $cs->lecturer->full_name }} • {{ $cs->schedule_date }}</div>
                    </div>
                @empty
                    <p style="color:#64748b; font-size:12.5px;">Tidak ada jadwal perkuliahan pada tanggal {{ $selectedDate }}.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif

<!-- MODAL EDIT PENGAJUAN PEMINJAMAN (MAHASISWA) -->
<div id="edit-reserve-modal" class="modal-backdrop">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title">Edit Pengajuan Peminjaman</div>
            <button type="button" onclick="closeEditReserveModal()" style="color:#64748b; font-size:18px;">✕</button>
        </div>

        <form id="edit-reserve-form" method="POST" action="" class="card-body">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Ruangan</label>
                <select name="room_id" id="edit-modal-room-select" class="form-control" required>
                    <optgroup label="Gedung GKT (Lantai 1 - 4)">
                        @foreach($allRooms->where('building.name', 'GKT')->where('status', 'available') as $r)
                            <option value="{{ $r->id }}">{{ $r->room_code }} — {{ $r->room_name }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Gedung TI (Laboratorium)">
                        @foreach($allRooms->where('building.name', 'TI')->where('status', 'available') as $r)
                            <option value="{{ $r->id }}">{{ $r->room_code }} — {{ $r->room_name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>

            <div class="form-row-3 form-group">
                <div>
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="reservation_date" id="edit-modal-date" required class="form-control">
                </div>
                <div>
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="start_time" id="edit-modal-start" min="08:00" max="21:30" required class="form-control">
                </div>
                <div>
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="end_time" id="edit-modal-end" min="08:00" max="21:30" required class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Keperluan Peminjaman</label>
                <textarea name="purpose" id="edit-modal-purpose" rows="3" required class="form-control"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                <button type="button" onclick="closeEditReserveModal()" class="btn btn-slate">Batal</button>
                <button type="submit" class="btn btn-navy">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditReserveModal(id, roomId, date, start, end, purpose) {
    const modal = document.getElementById('edit-reserve-modal');
    const form = document.getElementById('edit-reserve-form');
    form.action = '/mahasiswa/reservations/' + id;
    document.getElementById('edit-modal-room-select').value = String(roomId);
    document.getElementById('edit-modal-date').value = date;
    document.getElementById('edit-modal-start').value = start;
    document.getElementById('edit-modal-end').value = end;
    document.getElementById('edit-modal-purpose').value = purpose;
    modal.classList.add('show');
}
function closeEditReserveModal() {
    const modal = document.getElementById('edit-reserve-modal');
    modal.classList.remove('show');
}
</script>
@endsection
