<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomReserve — @yield('title', 'Sistem Peminjaman Ruangan')</title>
    <link rel="stylesheet" href="{{ asset('css/roomreserve-tailwind.css') }}?v=4">
</head>
<body>

    @guest
        @yield('content')
    @else
        @php
            $user = auth()->user();
            $roleRoute = 'role.' . $user->role;
            $roleLabels = [
                'mahasiswa' => 'Mahasiswa / Organisasi',
                'dosen'     => 'Dosen Pengampu',
                'kalab'     => 'Kepala Laboratorium',
                'baak'      => 'BAAK',
                'admin'     => 'Administrator',
            ];
        @endphp
        <div class="app-shell">
            <!-- SIDEBAR -->
            <aside id="app-sidebar" class="sidebar">
                <div>
                    <div class="sidebar-brand">
                        <a href="{{ route($roleRoute) }}" class="brand-box">
                            <div class="brand-icon">RR</div>
                            <div>
                                <div class="brand-title">RoomReserve</div>
                                <div class="brand-sub">GKT & Laboratorium TI</div>
                            </div>
                        </a>
                    </div>

                    <div class="nav-section-title">Menu Utama</div>
                    <div class="nav-list">
                        <a href="{{ route($roleRoute, ['tab' => 'dashboard']) }}"
                           class="nav-link {{ ($tab ?? 'dashboard') === 'dashboard' ? 'active' : '' }}">
                            <span>Dashboard</span>
                        </a>

                        @if($user->role === 'mahasiswa')
                            <a href="{{ route('role.mahasiswa', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span>Daftar Ruangan</span>
                                <span class="nav-pill">{{ isset($allRooms) ? $allRooms->count() : 17 }}</span>
                            </a>
                            <a href="{{ route('role.mahasiswa', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span>Kalender Ruangan</span>
                            </a>

                        @elseif($user->role === 'dosen')
                            <a href="{{ route('role.dosen', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span>Daftar Ruangan</span>
                                <span class="nav-pill">{{ isset($allRooms) ? $allRooms->count() : 17 }}</span>
                            </a>
                            <a href="{{ route('role.dosen', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span>Kalender Ruangan</span>
                            </a>

                        @elseif($user->role === 'kalab')
                            <a href="{{ route('role.kalab', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span>Kalender Ruangan</span>
                            </a>

                        @elseif($user->role === 'baak')
                            <a href="{{ route('role.baak', ['tab' => 'schedules']) }}"
                               class="nav-link {{ ($tab ?? '') === 'schedules' ? 'active' : '' }}">
                                <span>Jadwal Kuliah GKT</span>
                            </a>
                            <a href="{{ route('role.baak', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span>Data Ruangan</span>
                            </a>
                            <a href="{{ route('role.baak', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span>Kalender Ruangan</span>
                            </a>

                        @elseif($user->role === 'admin')
                            <a href="{{ route('role.admin', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span>Kalender Ruangan</span>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="sidebar-footer">
                    <div class="user-mini">
                        <div class="user-avatar">{{ strtoupper(substr($user->full_name, 0, 2)) }}</div>
                        <div style="min-width:0;">
                            <div class="user-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->full_name }}</div>
                            <div class="user-role">{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</div>
                        </div>
                    </div>
                    <a href="{{ route('logout') }}" class="btn btn-slate" style="width:100%; background:#1e293b; color:#cbd5e1; border-color:#334155;">
                        Keluar
                    </a>
                </div>
            </aside>

            <!-- AREA UTAMA -->
            <div class="main-area">
                <header class="topbar">
                    <div class="topbar-left">
                        <button type="button" onclick="toggleSidebar()" class="menu-toggle-btn">☰ Menu</button>
                        @hasSection('role_title')
                            <div class="page-title">@yield('role_title')</div>
                        @endif
                    </div>
                    <div class="topbar-right">
                        @if(in_array($user->role, ['mahasiswa', 'dosen']))
                            <button type="button" onclick="openReserveModal()" class="btn btn-navy">+ Ajukan Peminjaman</button>
                        @endif
                    </div>
                </header>

                <main class="content-container">
                    @if(session('success'))
                        <div class="reason-box approved" style="margin-top:0; font-weight:600;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="reason-box rejected" style="margin-top:0; font-weight:600;">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="reason-box rejected" style="margin-top:0;">
                            @foreach($errors->all() as $err)
                                <div>• {{ $err }}</div>
                            @endforeach
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <!-- MODAL 1: FORM PENGAJUAN PEMINJAMAN RUANGAN (MAHASISWA & DOSEN) -->
        @if(in_array($user->role, ['mahasiswa', 'dosen']) && isset($allRooms))
        <div id="reserve-modal" class="modal-backdrop">
            <div class="modal-card">
                <div class="card-header">
                    <div class="card-title">Form Pengajuan Peminjaman Ruangan</div>
                    <button type="button" onclick="closeReserveModal()" style="color:#64748b; font-size:18px;">✕</button>
                </div>

                <form method="POST" action="{{ route('reservations.store') }}" class="card-body">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Ruangan</label>
                        <select name="room_id" id="modal-room-select" onchange="updateRoutingPreview()" class="form-control">
                            <optgroup label="Gedung GKT (Lantai 1 - 4)">
                                @foreach($allRooms->where('building.name', 'GKT')->where('status', 'available') as $r)
                                    <option value="{{ $r->id }}" data-building="GKT" data-kalab="">
                                        {{ $r->room_code }} — {{ $r->room_name }} (Lantai {{ $r->floor }})
                                    </option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Gedung TI (Laboratorium)">
                                @foreach($allRooms->where('building.name', 'TI')->where('status', 'available') as $r)
                                    <option value="{{ $r->id }}" data-building="TI" data-kalab="{{ $r->labManager?->kalab?->full_name ?? 'Kalab TI' }}">
                                        {{ $r->room_code }} — {{ $r->room_name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-row-3 form-group">
                        <div>
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="reservation_date" id="modal-date" value="2026-11-11" onchange="updateRoutingPreview()" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="start_time" id="modal-start" value="08:30" onchange="updateRoutingPreview()" class="form-control">
                        </div>
                        <div>
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="end_time" id="modal-end" value="10:30" onchange="updateRoutingPreview()" class="form-control">
                        </div>
                    </div>

                    <div id="routing-preview-box" class="reason-box approved" style="margin-bottom:14px;">
                        <div id="routing-preview-text">Memeriksa ketersediaan jadwal...</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Keperluan Peminjaman</label>
                        <textarea name="purpose" rows="3" required placeholder="Tuliskan keperluan kegiatan..." class="form-control"></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px; padding-top:12px; border-top:1px solid #e2e8f0;">
                        <button type="button" onclick="closeReserveModal()" class="btn btn-slate">Batal</button>
                        <button type="submit" class="btn btn-navy">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- MODAL 2: TOLAK PEMINJAMAN -->
        <div id="reject-modal" class="modal-backdrop">
            <div class="modal-card" style="max-width:440px;">
                <div class="card-header">
                    <div class="card-title">Tolak Pengajuan Peminjaman</div>
                    <button type="button" onclick="closeRejectModal()" style="color:#64748b; font-size:18px;">✕</button>
                </div>

                <form method="POST" action="{{ route('reservations.decide') }}" class="card-body">
                    @csrf
                    <input type="hidden" name="decision" value="DITOLAK">
                    <input type="hidden" name="reservation_id" id="reject-res-id" value="">

                    <p style="font-size:13px; color:#475569; margin-bottom:12px;">
                        Pengajuan <strong id="reject-res-code"></strong> — <strong id="reject-res-room"></strong>
                    </p>

                    <div class="form-group">
                        <label class="form-label">Alasan Penolakan <span style="color:#e11d48;">*</span></label>
                        <textarea name="notes" id="reject-notes" rows="3" required placeholder="Tuliskan alasan penolakan..." class="form-control"></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" onclick="closeRejectModal()" class="btn btn-slate">Batal</button>
                        <button type="submit" class="btn btn-rose">Tolak Peminjaman</button>
                    </div>
                </form>
            </div>
        </div>
    @endguest

    <script>
        @php
            $jsSchedules = isset($allSchedules) ? $allSchedules->map(fn($cs) => [
                'room_id' => $cs->room_id,
                'schedule_date' => $cs->schedule_date,
                'start_time' => $cs->start_time,
                'end_time' => $cs->end_time,
                'course_name' => $cs->course_name,
                'lecturer_name' => $cs->lecturer?->full_name ?? 'Dosen',
            ])->values() : collect();

            $jsApproved = isset($allReservations) ? $allReservations->where('status', 'DISETUJUI')->map(fn($r) => [
                'room_id' => $r->room_id,
                'reservation_date' => $r->reservation_date,
                'start_time' => $r->start_time,
                'end_time' => $r->end_time,
                'purpose' => $r->purpose,
            ])->values() : collect();
        @endphp
        const classSchedules = @json($jsSchedules);
        const approvedReservations = @json($jsApproved);

        function toggleSidebar() {
            const sb = document.getElementById('app-sidebar');
            if (sb) sb.classList.toggle('open');
        }

        function filterRooms(building) {
            document.querySelectorAll('.room-card').forEach(card => {
                if (building === 'ALL' || card.dataset.building === building) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
            ['ALL', 'GKT', 'TI'].forEach(b => {
                const btn = document.getElementById('filter-' + b);
                if (btn) btn.className = (b === building) ? 'btn btn-navy' : 'btn btn-slate';
            });
        }

        function openReserveModal(roomId = null) {
            const modal = document.getElementById('reserve-modal');
            if (!modal) return;
            if (roomId) {
                document.getElementById('modal-room-select').value = String(roomId);
            }
            modal.classList.add('show');
            updateRoutingPreview();
        }

        function closeReserveModal() {
            const modal = document.getElementById('reserve-modal');
            if (modal) modal.classList.remove('show');
        }

        function updateRoutingPreview() {
            const select = document.getElementById('modal-room-select');
            if (!select || select.options.length === 0) return;
            const opt = select.options[select.selectedIndex];
            const roomId = parseInt(select.value, 10);
            const building = opt.dataset.building;
            const kalab = opt.dataset.kalab;
            const date = document.getElementById('modal-date').value;
            const start = document.getElementById('modal-start').value;
            const end = document.getElementById('modal-end').value;

            const box = document.getElementById('routing-preview-box');
            const text = document.getElementById('routing-preview-text');

            const conflictRes = approvedReservations.find(r =>
                parseInt(r.room_id, 10) === roomId &&
                r.reservation_date === date &&
                (start < r.end_time && end > r.start_time)
            );

            if (conflictRes) {
                box.className = 'reason-box rejected';
                text.innerHTML = '<strong>Jadwal Bentrok:</strong> Sudah digunakan untuk <em>"' + conflictRes.purpose + '"</em> (' + conflictRes.start_time + '–' + conflictRes.end_time + ').';
                return;
            }

            if (building === 'TI') {
                box.className = 'reason-box approved';
                text.innerHTML = 'Persetujuan dikirim ke <strong>Kepala Laboratorium: ' + kalab + '</strong>.';
                return;
            }

            const conflictClass = classSchedules.find(cs =>
                parseInt(cs.room_id, 10) === roomId &&
                cs.schedule_date === date &&
                (start < cs.end_time && end > cs.start_time)
            );

            if (conflictClass) {
                box.className = 'reason-box rejected';
                text.innerHTML = 'Terdapat jadwal kuliah <em>"' + conflictClass.course_name + '"</em>. Persetujuan dikirim ke <strong>Dosen: ' + conflictClass.lecturer_name + '</strong>.';
            } else {
                box.className = 'reason-box approved';
                text.innerHTML = 'Tidak ada jadwal kuliah pada jam ini. Persetujuan dikirim ke <strong>BAAK</strong>.';
            }
        }

        function openRejectModal(id, code, roomName) {
            document.getElementById('reject-res-id').value = id;
            document.getElementById('reject-res-code').textContent = code;
            document.getElementById('reject-res-room').textContent = roomName;
            document.getElementById('reject-notes').value = '';
            document.getElementById('reject-modal').classList.add('show');
        }

        function closeRejectModal() {
            document.getElementById('reject-modal').classList.remove('show');
        }
    </script>
</body>
</html>
