<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoomReserve — @yield('title', 'Sistem Peminjaman Ruangan')</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=2">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-icon.png') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/roomreserve-tailwind.css') }}?v=13">
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
            <!-- SIDEBAR KONSEP A: CLEAN ACADEMIC LIGHT (PUTIH BERSIH MENYATU DENGAN LOGO ASLI) -->
            <aside id="app-sidebar" class="sidebar">
                <div>
                    <div class="sidebar-brand">
                        <a href="{{ route($roleRoute) }}" class="brand-box">
                            <img src="{{ asset('images/logo-icon.png') }}?v=2" alt="RoomReserve" style="width:38px; height:38px; object-fit:contain; flex-shrink:0;">
                            <div style="min-width:0;">
                                <img src="{{ asset('images/logo-wordmark.png') }}?v=2" alt="RoomReserve" style="height:20px; width:auto; display:block; margin-bottom:2px;">
                                <div class="brand-sub">GKT &amp; Laboratorium TI</div>
                            </div>
                        </a>
                    </div>

                    <div class="nav-section-title">Menu Utama</div>
                    <div class="nav-list">
                        <a href="{{ route($roleRoute, ['tab' => 'dashboard']) }}"
                           class="nav-link {{ ($tab ?? 'dashboard') === 'dashboard' ? 'active' : '' }}">
                            <span class="nav-link-inner">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect></svg>
                                <span>Dashboard</span>
                            </span>
                        </a>

                        @if($user->role === 'mahasiswa')
                            <a href="{{ route('role.mahasiswa', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9v.01"></path><path d="M9 12v.01"></path><path d="M9 15v.01"></path><path d="M9 18v.01"></path></svg>
                                    <span>Daftar Ruangan</span>
                                </span>
                                <span class="nav-pill">{{ isset($allRooms) ? $allRooms->count() : 18 }}</span>
                            </a>
                            <a href="{{ route('role.mahasiswa', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>Kalender Ruangan</span>
                                </span>
                            </a>

                        @elseif($user->role === 'dosen')
                            <a href="{{ route('role.dosen', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path><path d="M9 9v.01"></path><path d="M9 12v.01"></path><path d="M9 15v.01"></path><path d="M9 18v.01"></path></svg>
                                    <span>Daftar Ruangan</span>
                                </span>
                                <span class="nav-pill">{{ isset($allRooms) ? $allRooms->count() : 18 }}</span>
                            </a>
                            <a href="{{ route('role.dosen', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>Kalender Ruangan</span>
                                </span>
                            </a>

                        @elseif($user->role === 'kalab')
                            <a href="{{ route('role.kalab', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
                                    <span>Daftar Ruangan</span>
                                </span>
                                <span class="nav-pill">{{ isset($allRooms) ? $allRooms->count() : 18 }}</span>
                            </a>
                            <a href="{{ route('role.kalab', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>Kalender Ruangan</span>
                                </span>
                            </a>

                        @elseif($user->role === 'baak')
                            <a href="{{ route('role.baak', ['tab' => 'students']) }}"
                               class="nav-link {{ ($tab ?? '') === 'students' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <span>Data Mahasiswa</span>
                                </span>
                            </a>
                            <a href="{{ route('role.baak', ['tab' => 'schedules']) }}"
                               class="nav-link {{ ($tab ?? '') === 'schedules' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path></svg>
                                    <span>Jadwal Kuliah &amp; Lab</span>
                                </span>
                            </a>
                            <a href="{{ route('role.baak', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
                                    <span>Data Ruangan &amp; Lab</span>
                                </span>
                            </a>
                            <a href="{{ route('role.baak', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>Kalender Ruangan</span>
                                </span>
                            </a>

                        @elseif($user->role === 'admin')
                            <a href="{{ route('role.admin', ['tab' => 'rooms']) }}"
                               class="nav-link {{ ($tab ?? '') === 'rooms' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
                                    <span>Cek Kelas &amp; Lab (View)</span>
                                </span>
                                <span class="nav-pill">{{ isset($allRooms) ? $allRooms->count() : 18 }}</span>
                            </a>
                            <a href="{{ route('role.admin', ['tab' => 'calendar']) }}"
                               class="nav-link {{ ($tab ?? '') === 'calendar' ? 'active' : '' }}">
                                <span class="nav-link-inner">
                                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <span>Kalender Ruangan</span>
                                </span>
                            </a>
                        @endif

                        <a href="{{ route('profile.show') }}"
                           class="nav-link {{ ($tab ?? '') === 'profile' ? 'active' : '' }}">
                            <span class="nav-link-inner">
                                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <span>Profil Saya</span>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-footer">
                    <a href="{{ route('profile.show') }}" class="user-mini" title="Buka Profil Saya">
                        @if(!empty($user->avatar))
                            <img src="{{ $user->avatar }}" alt="{{ $user->full_name }}"
                                 style="width:34px; height:34px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1px solid rgba(147, 197, 253, 0.45);">
                        @else
                            <div class="user-avatar">{{ strtoupper(substr($user->full_name, 0, 2)) }}</div>
                        @endif
                        <div style="min-width:0;">
                            <div class="user-name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $user->full_name }}</div>
                            <div class="user-role">{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</div>
                        </div>
                    </a>
                    <a href="{{ route('logout') }}" class="btn sidebar-logout-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>Keluar</span>
                    </a>
                </div>
            </aside>

            <!-- AREA UTAMA -->
            <div class="main-area">
                <header class="topbar">
                    <div class="topbar-left">
                        <button type="button" onclick="toggleSidebar()" class="menu-toggle-btn">☰ Menu</button>
                        <div class="topbar-clock" title="Waktu Indonesia Barat (WIB)">
                            <span class="topbar-clock-dot"></span>
                            <span>{{ now()->translatedFormat('d M Y') }} • {{ now()->format('H:i') }} WIB</span>
                        </div>
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
                            <input type="date" name="reservation_date" id="modal-date" value="{{ date('Y-m-d') }}" onchange="updateRoutingPreview()" class="form-control">
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
                'class_name' => $cs->class_name ?? '',
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

        function openReserveModal(roomId = null, targetDate = null) {
            const modal = document.getElementById('reserve-modal');
            if (!modal) return;
            if (roomId) {
                document.getElementById('modal-room-select').value = String(roomId);
            }
            if (targetDate) {
                document.getElementById('modal-date').value = targetDate;
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
                text.innerHTML = '<strong>Jadwal Bentrok:</strong> Sudah disetujui untuk <em>"' + conflictRes.purpose + '"</em> (' + conflictRes.start_time + '–' + conflictRes.end_time + ').';
                return;
            }

            const conflictClass = classSchedules.find(cs =>
                parseInt(cs.room_id, 10) === roomId &&
                cs.schedule_date === date &&
                (start < cs.end_time && end > cs.start_time)
            );

            if (building === 'TI') {
                if (conflictClass) {
                    const clsLabel = conflictClass.class_name ? ' (' + conflictClass.class_name + ')' : '';
                    box.className = 'reason-box rejected';
                    text.innerHTML = 'Terdapat jadwal kuliah/praktikum <em>"' + conflictClass.course_name + clsLabel + '"</em> (' + conflictClass.lecturer_name + '). Persetujuan dikirim ke <strong>Kepala Laboratorium: ' + kalab + '</strong>.';
                } else {
                    box.className = 'reason-box approved';
                    text.innerHTML = 'Persetujuan dikirim ke <strong>Kepala Laboratorium: ' + kalab + '</strong>.';
                }
                return;
            }

            if (conflictClass) {
                const clsLabel = conflictClass.class_name ? ' (' + conflictClass.class_name + ')' : '';
                box.className = 'reason-box rejected';
                text.innerHTML = 'Terdapat jadwal kuliah <em>"' + conflictClass.course_name + clsLabel + '"</em>. Persetujuan dikirim ke <strong>Dosen Pengampu: ' + conflictClass.lecturer_name + '</strong>.';
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
