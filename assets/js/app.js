(() => {
    const dashboardApi = '/?page=api/dashboard';
    const alarmButton = document.getElementById('enableAlarmSound');
    const alarmBadge = document.getElementById('alarmStatusBadge');
    const deviceGrid = document.getElementById('deviceGrid');
    const activeAlarmTable = document.getElementById('activeAlarmTable');

    if (!alarmButton || !alarmBadge || !deviceGrid || !activeAlarmTable) {
        return;
    }

    let audioEnabled = false;
    let alarmPlaying = false;
    let audioContext = null;
    let oscillator = null;
    let alarmInterval = null;
    let lastAlarmSignature = '';

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
        gainNode.gain.value = 0.05;
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        oscillator.start();

        alarmPlaying = true;
        alarmInterval = window.setInterval(() => {
            if (oscillator) {
                oscillator.frequency.value = oscillator.frequency.value === 880 ? 1320 : 880;
            }
        }, 350);
    };

    alarmButton.addEventListener('click', async () => {
        audioEnabled = true;
        alarmButton.classList.remove('btn-outline-light');
        alarmButton.classList.add('btn-warning');
        alarmButton.textContent = 'Suara Alarm Aktif';

        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        await audioContext.resume();
    });

    const renderDevices = (devices) => {
        if (!Array.isArray(devices)) {
            return;
        }

        const cards = [];
        devices.forEach((device) => {
            const redActive = Number(device.red_active) === 1;
            const blueActive = Number(device.blue_active) === 1;
            cards.push(`
                <div class="col-12 col-md-6 col-xxl-4">
                    <div class="device-card ${(redActive || blueActive) ? 'device-alert' : ''}" data-btn-code="${device.btn_code}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <div class="text-uppercase small text-muted">Lokasi</div>
                                <div class="h5 mb-0">${device.location}</div>
                            </div>
                            <span class="badge ${device.status === 'Connected' ? 'bg-success' : 'bg-secondary'}">${device.status}</span>
                        </div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="lamp-stack">
                                <span class="lamp lamp-red ${redActive ? 'is-active blink' : ''}"></span>
                                <span class="lamp lamp-blue ${blueActive ? 'is-active blink' : ''}"></span>
                            </div>
                            <div>
                                <div class="fw-semibold">Kode: ${device.btn_code}</div>
                                <div class="text-muted small">IP: ${device.ip}</div>
                                <div class="text-muted small">Last ping: ${device.last_ping}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="btn btn-sm btn-outline-danger disabled ${redActive ? 'active-state' : ''}">Codered ${redActive ? 'ON' : 'OFF'}</span>
                            <span class="btn btn-sm btn-outline-info disabled ${blueActive ? 'active-state' : ''}">Codeblue ${blueActive ? 'ON' : 'OFF'}</span>
                        </div>
                    </div>
                </div>
            `);
        });
        deviceGrid.innerHTML = cards.join('');
    };

    const renderAlarmTable = (alarms) => {
        if (!Array.isArray(alarms) || alarms.length === 0) {
            activeAlarmTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Tidak ada alarm aktif</td></tr>';
            return;
        }

        activeAlarmTable.innerHTML = alarms.map((alarm) => `
            <tr>
                <td>${alarm.location ?? '-'}</td>
                <td>${alarm.btn_code}</td>
                <td><span class="badge ${alarm.btn_type === 'Red' ? 'bg-danger' : 'bg-info'}">${alarm.btn_type}</span></td>
                <td><span class="badge bg-warning text-dark">ON</span></td>
                <td>${alarm.last_ping ?? '-'}</td>
            </tr>
        `).join('');
    };

    const syncDashboard = async () => {
        try {
            const response = await fetch(dashboardApi, { headers: { Accept: 'application/json' } });
            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const activeAlarms = Array.isArray(payload.active_alarms) ? payload.active_alarms : [];
            const signature = JSON.stringify(activeAlarms);

            alarmBadge.textContent = `Alarm aktif: ${payload.active_alarm_count ?? activeAlarms.length}`;
            renderDevices(payload.devices ?? []);
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
        }
    };

    syncDashboard();
    window.setInterval(syncDashboard, 5000);
})();
