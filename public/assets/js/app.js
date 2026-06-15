(() => {
    const body = document.body;
    const dashboardRole = body?.dataset?.dashboardRole || 'admin';
    const dashboardApi = body?.dataset?.dashboardApi || '/?page=api/dashboard';
    const alarmButton = document.getElementById('enableAlarmSound');
    const fullscreenButton = document.getElementById('toggleFullscreen');
    const fullscreenTarget = document.getElementById('dashboardFullscreenTarget');
    const alarmBadge = document.getElementById('alarmStatusBadge');
    const deviceCarousel = document.getElementById('deviceCarousel');
    const deviceCarouselTrack = document.getElementById('deviceCarouselTrack');
    const carouselCounter = document.getElementById('carouselCounter');
    const carouselLocationLabel = document.getElementById('carouselLocationLabel');
    const activeAlarmTable = document.getElementById('activeAlarmTable');
    const activeAlarm = document.getElementById('activeAlarm');
    const activeBlue = document.getElementById('activeBlue');
    const activeRed = document.getElementById('activeRed');

    if (!alarmButton || !alarmBadge || !deviceCarousel || !deviceCarouselTrack || !carouselCounter || !carouselLocationLabel || !activeAlarmTable) {
        if (!fullscreenButton || !fullscreenTarget) {
            return;
        }
    }

    let audioEnabled = true;
    let alarmPlaying = false;
    let audioContext = null;
    let oscillator = null;
    let alarmInterval = null;
    let lastAlarmSignature = '';
    let carouselDevices = [];
    let carouselFallbackTimer = null;
    let syncInProgress = false;
    const carouselFallbackIntervalMs = 10000;

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const stopTone = () => {
        if (alarmInterval) {
            clearInterval(alarmInterval);
            alarmInterval = null;
        }
        if (oscillator) {
            oscillator.stop();
            oscillator.disconnect();
            oscillator = null;
        }
        alarmPlaying = false;
    };

    const clearCarouselTimer = () => {
        if (carouselFallbackTimer) {
            clearInterval(carouselFallbackTimer);
            carouselFallbackTimer = null;
        }
    };

    const updateCarouselView = () => {
        if (!deviceCarouselTrack || !carouselCounter || !carouselLocationLabel) {
            return;
        }

        const slideCount = carouselDevices.length;
        if (slideCount === 0) {
            deviceCarouselTrack.style.setProperty('--carousel-duration', '24s');
            carouselCounter.textContent = '0 kartu';
            carouselLocationLabel.textContent = 'Belum ada lokasi';
            return;
        }

        const durationSeconds = Math.max(18, slideCount * 4);
        deviceCarouselTrack.style.setProperty('--carousel-duration', `${durationSeconds}s`);
        carouselCounter.textContent = `${slideCount} kartu`;
        carouselLocationLabel.textContent = 'Scroll berkelanjutan';
    };

    const buildDeviceSlide = (device) => {
        const redActive = Number(device.red_active) === 1;
        const blueActive = Number(device.blue_active) === 1;
        const roleActive = dashboardRole === 'codered' ? redActive : (dashboardRole === 'codeblue' ? blueActive : (redActive || blueActive));
        const isConnected = device.status === 'Connected';

        return `
            <div class="device-slide">
                <div class="device-card ${roleActive ? 'device-alert' : ''}" data-btn-code="${escapeHtml(device.btn_code)}">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="text-uppercase small text-muted"><i class="bi bi-geo-alt-fill"></i> Lokasi</div>
                            <div class="h5 mb-0">${escapeHtml(device.location)}</div>
                        </div>
                        <span class="badge ${isConnected ? 'bg-success' : 'bg-secondary'}">${escapeHtml(device.status)}</span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="lamp-stack">
                            ${dashboardRole === 'codered' ? `<span class="lamp lamp-red ${roleActive ? 'is-active blink' : ''}"></span>` : ''}
                            ${dashboardRole === 'codeblue' ? `<span class="lamp lamp-blue ${roleActive ? 'is-active blink' : ''}"></span>` : ''}
                            ${dashboardRole === 'admin' ? `<span class="lamp lamp-red ${redActive ? 'is-active blink' : ''}"></span><span class="lamp lamp-blue ${blueActive ? 'is-active blink' : ''}"></span>` : ''}
                        </div>
                        <div>
                            <div class="fw-semibold">Kode: ${escapeHtml(device.btn_code)}</div>
                            <div class="text-muted small">IP: ${escapeHtml(device.ip)}</div>
                            <div class="text-muted small">Last ping: ${escapeHtml(device.last_ping)}</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        ${dashboardRole === 'codered' ? `<span class="btn btn-sm disabled ${roleActive ? 'btn-danger active-state' : 'btn-outline-danger'}"><i class="bi bi-bell-fill me-2"></i> Codered ${roleActive ? 'ON' : 'OFF'}</span>` : ''}
                        ${dashboardRole === 'codeblue' ? `<span class="btn btn-sm disabled ${roleActive ? 'btn-info active-state' : 'btn-outline-info'}"><i class="bi bi-bell-fill me-2"></i> Codeblue ${roleActive ? 'ON' : 'OFF'}</span>` : ''}
                        ${dashboardRole === 'admin' ? `<span class="btn btn-sm disabled ${redActive ? 'btn-danger active-state' : 'btn-outline-danger'}"><i class="bi bi-bell-fill me-2"></i> Codered ${redActive ? 'ON' : 'OFF'}</span><span class="btn btn-sm disabled ${blueActive ? 'btn-info active-state' : 'btn-outline-info'}"><i class="bi bi-bell-fill me-2"></i> Codeblue ${blueActive ? 'ON' : 'OFF'}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    };

    const renderDeviceCarousel = (devices) => {
        carouselDevices = Array.isArray(devices) ? devices : [];

        if (!deviceCarouselTrack) {
            return;
        }

        if (carouselDevices.length === 0) {
            deviceCarouselTrack.innerHTML = `
                <div class="device-slide device-slide-empty">
                    <div class="device-card device-card-empty">
                        <div class="display-6 mb-2"><i class="bi bi-broadcast"></i></div>
                        <h3 class="h4 mb-2">Belum ada button terdaftar</h3>
                        <p class="text-muted mb-0">Admin dapat menambahkan satelit baru dari menu Manage Button.</p>
                    </div>
                </div>
            `;
            updateCarouselView();
            clearCarouselTimer();
        } else {
            const scrollingSlides = [...carouselDevices, ...carouselDevices];
            deviceCarouselTrack.innerHTML = scrollingSlides.map(buildDeviceSlide).join('');
            updateCarouselView();
            clearCarouselTimer();

            deviceCarouselTrack.onanimationiteration = async () => {
                if (!syncInProgress) {
                    await syncDashboard();
                }
            };
        }

        if (carouselDevices.length === 0) {
            carouselFallbackTimer = window.setInterval(async () => {
                if (!syncInProgress) {
                    await syncDashboard();
                }
            }, carouselFallbackIntervalMs);
        }
    };

    const playTone = () => {
        if (!audioEnabled || alarmPlaying) {
            return;
        }

        audioContext = audioContext || new (window.AudioContext || window.webkitAudioContext)();
        audioContext.resume();

        oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        oscillator.type = 'sawtooth';
        oscillator.frequency.value = 880;
        gainNode.gain.value = 1;
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.start();

        alarmPlaying = true;
        alarmInterval = window.setInterval(() => {
            if (oscillator) {
                oscillator.frequency.value = oscillator.frequency.value === 880 ? 1200 : 880;
            }
        }, 350);
    };

    alarmButton.addEventListener('click', async () => {
        // Inisialisasi AudioContext pada klik pertama (aturan browser)
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        await audioContext.resume();

        // Logika Toggle
        if (audioEnabled) {
            // Matikan audio jika sedang aktif
            audioEnabled = false;
            stopTone(); // Hentikan suara jika sedang berbunyi
            
            // Ubah tampilan tombol menjadi Nonaktif
            alarmButton.classList.remove('btn-warning');
            alarmButton.classList.add('btn-outline-light');
            alarmButton.textContent = 'Suara Alarm Nonaktif';
        } else {
            // Aktifkan audio jika sedang mati
            audioEnabled = true;
            
            // Ubah tampilan tombol menjadi Aktif
            alarmButton.classList.remove('btn-outline-light');
            alarmButton.classList.add('btn-warning');
            alarmButton.textContent = 'Suara Alarm Aktif';
        }
    });

    if (fullscreenButton && fullscreenTarget) {
        const updateFullscreenButton = () => {
            const isFullscreen = document.fullscreenElement === fullscreenTarget;
            fullscreenButton.innerHTML = isFullscreen
                ? '<i class="bi bi-fullscreen-exit me-2"></i>Keluar Fullscreen'
                : '<i class="bi bi-arrows-fullscreen me-2"></i>Fullscreen';
        };

        fullscreenButton.addEventListener('click', async () => {
            try {
                if (document.fullscreenElement === fullscreenTarget) {
                    await document.exitFullscreen();
                    return;
                }

                await fullscreenTarget.requestFullscreen();
            } catch (error) {
                console.error('Fullscreen toggle failed', error);
            }
        });

        document.addEventListener('fullscreenchange', updateFullscreenButton);
        updateFullscreenButton();
    }

    const renderAlarmTable = (alarms) => {
        if (!Array.isArray(alarms) || alarms.length === 0) {
            activeAlarmTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada alarm aktif</td></tr>';
            return;
        }

        activeAlarmTable.innerHTML = alarms.map((alarm) => `
            <tr class="fw-bold fs-5">
                <td>${alarm.location ?? '-'}</td>
                <td>${alarm.btn_code}</td>
                <td><span class="badge ${alarm.btn_type === 'Red' ? 'bg-danger' : 'bg-info'}">${alarm.btn_type}</span></td>
                <td><span class="badge bg-warning text-dark">ON</span></td>
                <td>${alarm.last_ping ?? '-'}</td>
            </tr>
        `).join('');
    };

    const syncDashboard = async () => {
        if (syncInProgress) {
            return;
        }

        syncInProgress = true;

        try {
            const response = await fetch(dashboardApi, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const activeAlarms = Array.isArray(payload.active_alarms) ? payload.active_alarms : [];
            const signature = JSON.stringify(activeAlarms);
            console.log(payload);
            // alarmBadge.textContent = `Alarm aktif: ${payload.active_alarm_count ?? activeAlarms.length}`;
            if (payload.dashboard_role === 'codeblue') {
                activeAlarm.innerHTML = payload.summary.active_blue;
            } else if (payload.dashboard_role === 'codered') {
                activeAlarm.innerHTML = payload.summary.active_red;
            } else {
                activeBlue.innerHTML = payload.summary.active_blue ?? 0;
                activeRed.innerHTML = payload.summary.active_red ?? 0;
            }
            renderDeviceCarousel(payload.devices ?? []);
            renderAlarmTable(activeAlarms);

            if (activeAlarms.length > 0 && signature !== lastAlarmSignature) {
                playTone();
            }

            if (activeAlarms.length === 0) {
                stopTone();
            }

            lastAlarmSignature = signature;
        } catch (error) {
            console.error('Failed to sync dashboard', error);
        } finally {
            syncInProgress = false;
        }
    };

    syncDashboard();
    window.setInterval(syncDashboard, 5000);
})();
