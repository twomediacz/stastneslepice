// Šťastné slepice – hlavní JS
'use strict';

const App = {

    // --- API helper ---
    api: {
        async get(url) {
            const res = await fetch(url);
            if (res.status === 401) { window.location.href = '/login'; return null; }
            return res.json();
        },
        async post(url, data) {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (res.status === 401) { window.location.href = '/login'; return null; }
            return res.json();
        },
        async upload(url, formData) {
            const res = await fetch(url, { method: 'POST', body: formData });
            if (res.status === 401) { window.location.href = '/login'; return null; }
            return res.json();
        }
    },

    // --- Zápis vajec ---
    eggs: {
        init() {
            const form = document.getElementById('egg-form');
            if (!form) return;

            // Flatpickr na pole datum
            const dateInput = document.getElementById('egg-date');
            if (dateInput && typeof flatpickr !== 'undefined') {
                flatpickr(dateInput, {
                    locale: 'cs',
                    dateFormat: 'Y-m-d',
                    defaultDate: 'today'
                });
            }

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const data = {
                    date: fd.get('date'),
                    egg_count: parseInt(fd.get('egg_count')) || 0,
                    note: fd.get('note') || null
                };

                const result = await App.api.post('/api/eggs', data);
                if (!result) return;

                if (result.error) {
                    alert(result.error);
                    return;
                }

                App.eggs.updateTable(result.record);
                App.eggs.updateStats(result.total, result.average);
                App.charts.loadEggChart();
                App.eggs.toggleForm();
            });
        },

        toggleForm(show) {
            const wrap = document.getElementById('egg-form-wrap');
            if (!wrap) return;
            if (typeof show === 'boolean') {
                wrap.style.display = show ? '' : 'none';
            } else {
                wrap.style.display = wrap.style.display === 'none' ? '' : 'none';
            }
        },

        edit(tr) {
            if (!tr) return;
            const form = document.getElementById('egg-form');
            const dateInput = document.getElementById('egg-date');
            if (!form || !dateInput) return;

            dateInput.value = tr.dataset.date;
            // Update flatpickr if active
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(tr.dataset.date);
            form.egg_count.value = tr.dataset.count;
            form.note.value = tr.dataset.note;
            App.autoResize.resize(form.note);

            App.eggs.toggleForm(true);
        },

        async remove(id) {
            if (!confirm('Smazat záznam?')) return;
            const result = await App.api.post('/api/eggs/delete', { id });
            if (!result || result.error) return;

            const row = document.querySelector(`#egg-table-body tr[data-id="${id}"]`);
            if (row) row.remove();

            App.eggs.updateStats(result.total, result.average);
            App.charts.loadEggChart();
        },

        updateTable(record) {
            if (!record) return;
            const tbody = document.getElementById('egg-table-body');
            if (!tbody) return;

            const date = new Date(record.record_date);
            const dateStr = App.formatDate(date);
            const noteEsc = App.escapeHtml(record.note || '');

            const actionsHtml = window.__isLoggedIn ? `
                <td class="egg-actions">
                    <button class="btn-icon" onclick="App.eggs.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                    <button class="btn-icon btn-icon--danger" onclick="App.eggs.remove(${record.id})" title="Smazat">&times;</button>
                </td>` : '';
            const html = `<tr data-id="${record.id}" data-date="${record.record_date}" data-count="${record.egg_count}" data-note="${noteEsc}">
                <td>${dateStr}</td>
                <td><span class="egg-count-badge">${record.egg_count}</span></td>
                <td>${noteEsc}</td>
                ${actionsHtml}
            </tr>`;

            const existingRow = tbody.querySelector(`tr[data-date="${record.record_date}"]`);
            if (existingRow) {
                existingRow.outerHTML = html;
            } else {
                tbody.insertAdjacentHTML('afterbegin', html);
            }
        },

        updateStats(total, avg) {
            const totalEl = document.getElementById('stat-total');
            const avgEl = document.getElementById('stat-avg');
            if (totalEl) {
                totalEl.textContent = '\u{1F95A} ' + total.toLocaleString('cs-CZ');
                totalEl.classList.toggle('stat-card__value--small', total > 999);
            }
            if (avgEl) avgEl.textContent = '\u{1F423} ' + avg.toLocaleString('cs-CZ', { minimumFractionDigits: 1 });
        }
    },

    // --- Klima ---
    climate: {
        async refresh() {
            const data = await App.api.get('/api/climate/latest');
            if (!data) return;

            const cards = document.querySelectorAll('.climate-val strong');
            if (data.coop && cards.length >= 2) {
                cards[0].textContent = data.coop.temperature + '°C';
                cards[1].textContent = data.coop.humidity + '%';
            }
            if (data.outdoor && cards.length >= 4) {
                cards[2].textContent = data.outdoor.temperature + '°C';
                cards[3].textContent = data.outdoor.humidity + '%';
            }
        }
    },

    // --- Grafy ---
    charts: {
        climateChart: null,
        eggChart: null,

        init() {
            if (typeof Chart === 'undefined') return;
            App.charts.loadClimateChart();
            App.charts.loadEggChart();

            const toggle = document.getElementById('climate-period-toggle');
            if (toggle) {
                toggle.addEventListener('click', (e) => {
                    const btn = e.target.closest('.period-toggle__btn');
                    if (!btn || btn.classList.contains('period-toggle__btn--active')) return;
                    toggle.querySelector('.period-toggle__btn--active')?.classList.remove('period-toggle__btn--active');
                    btn.classList.add('period-toggle__btn--active');
                    App.charts.loadClimateChart(btn.dataset.period);
                });
            }

            const eggsToggle = document.getElementById('eggs-period-toggle');
            if (eggsToggle) {
                eggsToggle.addEventListener('click', (e) => {
                    const btn = e.target.closest('.period-toggle__btn');
                    if (!btn || btn.classList.contains('period-toggle__btn--active')) return;
                    eggsToggle.querySelector('.period-toggle__btn--active')?.classList.remove('period-toggle__btn--active');
                    btn.classList.add('period-toggle__btn--active');
                    App.charts.loadEggChart(btn.dataset.period);
                });
            }
        },

        // Teplota a vlhkost: dva panely nad sebou se společnou časovou osou
        // (nahoře teplota, dole vlhkost). Barva = místo, legenda jen Kurník / Venku.
        async loadClimateChart(period) {
            period = period || 'day';
            const canvas = document.getElementById('chart-climate');
            if (!canvas) return;

            const isDay = period === 'day';
            const PLACES = [
                { key: 'coop',    name: 'Kurník', color: '#b87949', band: 'rgba(184, 121, 73, 0.18)' },
                { key: 'outdoor', name: 'Výběh',  color: '#26a08a', band: 'rgba(38, 160, 138, 0.18)' }
            ];
            const METRICS = [
                // key = předpona sloupců temp_min/temp_max, hum_min/hum_max u denních a měsíčních souhrnů
                { key: 'temp', field: 'temperature', name: 'Teplota', unit: ' °C', axis: 'yTemp' },
                { key: 'hum',  field: 'humidity',    name: 'Vlhkost', unit: ' %',  axis: 'yHum' }
            ];
            // Měří se každých 5 minut. Díra delší než 15 minut (3 vynechaná měření) přeruší čáru.
            const GAP = 15 * 60 * 1000;
            const GRID = 'rgba(0, 0, 0, 0.06)';

            const num = (v) => { const n = parseFloat(v); return Number.isFinite(n) ? n : null; };
            const fmtNum = (v) => v.toLocaleString('cs-CZ', { maximumFractionDigits: 1 });
            const fmtTime = (ms) => new Date(ms).toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit' });
            const fmtDateTime = (ms) => new Date(ms).toLocaleString('cs-CZ', {
                day: 'numeric', month: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            const baseStyle = (p, m) => ({
                loc: p.key,
                metric: m.key,
                label: m.name + ' ' + p.name.toLowerCase(),
                yAxisID: m.axis,
                borderColor: p.color,
                backgroundColor: p.band,
                pointBackgroundColor: p.color,
                pointBorderColor: p.color,
                borderWidth: 2,
                cubicInterpolationMode: 'monotone',   // bez falešných vrcholů mezi body
                pointHoverRadius: 4,
                pointHitRadius: 8
            });

            let labels;
            let xRange = null;
            const datasets = [];

            if (isDay) {
                const data = await App.api.get('/api/climate/history?hours=24');
                if (!data) return;

                // Kurník a venek se párují podle času, ne podle pořadí. Když jedno čidlo
                // nějaké měření vynechá (input.php ho pak neuloží), nic se neposune.
                const rows = {};
                PLACES.forEach((p) => (data[p.key] || []).forEach((r) => {
                    const t = new Date(String(r.recorded_at).replace(' ', 'T')).getTime();
                    if (!Number.isFinite(t)) return;
                    const minute = Math.round(t / 60000) * 60000;
                    (rows[minute] = rows[minute] || {})[p.key] = r;
                }));
                const times = Object.keys(rows).map(Number).sort((a, b) => a - b);

                if (times.length) {
                    let min = times[0];
                    let max = times[times.length - 1];
                    if (max - min < 30 * 60000) {   // aspoň půlhodinové okno, i kdyby byl jen jeden bod
                        const mid = (min + max) / 2;
                        min = mid - 15 * 60000;
                        max = mid + 15 * 60000;
                    }
                    const pad = (max - min) * 0.015;   // krajní body nesmí být useknuté o okraj
                    xRange = { min: min - pad, max: max + pad };
                }

                // Pro každou řadu z jejích vlastních bodů (null = to místo v tu chvíli neměřilo):
                //  gapBefore[i] – úsek končící bodem i leží v díře delší než GAP → nekreslit
                //  isolated[i]  – bod nemá souseda do GAP → nakreslit jako tečku, jinak by nebyl vidět
                const analyse = (points) => {
                    const gapBefore = [];
                    const isolated = [];
                    let last = -1;
                    points.forEach((pt, i) => {
                        if (pt.y == null) return;
                        const gap = last < 0 || pt.x - points[last].x > GAP;
                        if (last >= 0 && gap) {
                            for (let j = last + 1; j <= i; j++) gapBefore[j] = true;
                        }
                        isolated[i] = gap;                                    // zatím jen „vlevo nic“
                        if (last >= 0 && !gap) isolated[last] = false;        // předchozí má souseda vpravo
                        last = i;
                    });
                    return { gapBefore, isolated };
                };

                METRICS.forEach((m) => PLACES.forEach((p) => {
                    const points = times.map((t) => ({
                        x: t,
                        y: rows[t][p.key] ? num(rows[t][p.key][m.field]) : null
                    }));
                    datasets.push({
                        ...baseStyle(p, m),
                        data: points,
                        ...analyse(points),
                        fill: false,
                        spanGaps: true,   // jedno dvě vynechaná měření se přemostí, delší díru řeší segment
                        segment: {
                            borderColor: (ctx) => (ctx.chart.data.datasets[ctx.datasetIndex].gapBefore[ctx.p1DataIndex]
                                ? 'transparent' : undefined)
                        },
                        pointRadius: (ctx) => (ctx.dataset.isolated[ctx.dataIndex] ? 3 : 0)
                    });
                }));
            } else {
                let url, key, fmtKey;
                if (period === 'year') {
                    url = '/api/climate/history?group=month&months=12';
                    key = 'month';
                    fmtKey = (k) => {
                        const [y, m] = k.split('-');
                        return new Date(parseInt(y), parseInt(m) - 1).toLocaleDateString('cs-CZ', { month: 'short' });
                    };
                } else {
                    const daysMap = { week: 7, month: 30 };
                    url = '/api/climate/history?group=day&days=' + (daysMap[period] || 7);
                    key = 'date';
                    fmtKey = (k) => new Date(k + 'T00:00:00').toLocaleDateString('cs-CZ', { day: 'numeric', month: 'numeric' });
                }
                const data = await App.api.get(url);
                if (!data) return;

                // Společná osa = všechny dny/měsíce z obou míst; chybějící den = mezera, ne posun
                const rows = {};
                PLACES.forEach((p) => (data[p.key] || []).forEach((r) => {
                    (rows[r[key]] = rows[r[key]] || {})[p.key] = r;
                }));
                const keys = Object.keys(rows).sort();
                labels = keys.map(fmtKey);
                const column = (loc, field) => keys.map((k) => (rows[k][loc] ? num(rows[k][loc][field]) : null));

                // Pás mezi minimem a maximem: plná čára = maximum, tenká čárkovaná = minimum
                METRICS.forEach((m) => PLACES.forEach((p) => {
                    const mins = column(p.key, m.key + '_min');
                    datasets.push(
                        {
                            ...baseStyle(p, m),
                            data: column(p.key, m.key + '_max'),
                            minData: mins,
                            fill: '+1',
                            pointRadius: 2.5
                        },
                        {
                            ...baseStyle(p, m),
                            isMin: true,
                            data: mins,
                            fill: false,
                            borderWidth: 1,
                            borderDash: [3, 3],
                            pointRadius: 0,
                            pointHitRadius: 0
                        }
                    );
                }));
            }

            // Rozsah osy y: nikdy užší než `span` (jinak by šum o pár desetin vypadal jako skok),
            // kousek odstupu od okraje panelu a u vlhkosti nikdy mimo 0–100 %.
            const axisRange = (span, lo, hi) => (axis) => {
                let min = axis.min;
                let max = axis.max;
                if (!Number.isFinite(min) || !Number.isFinite(max)) return;
                if (max - min < span) {
                    const mid = (min + max) / 2;
                    min = mid - span / 2;
                    max = mid + span / 2;
                }
                const pad = (max - min) * 0.08;
                min -= pad;
                max += pad;
                if (lo != null && min < lo) { max = Math.min(hi != null ? hi : Infinity, max + (lo - min)); min = lo; }
                if (hi != null && max > hi) { min = Math.max(lo != null ? lo : -Infinity, min - (max - hi)); max = hi; }
                axis.min = min;
                axis.max = max;
            };
            const panel = (title, span, lo, hi) => ({
                type: 'linear',
                position: 'left',
                stack: 'climate',      // osy y nad sebou ve stejném grafu
                stackWeight: 1,
                title: { display: true, text: title },
                grid: { color: GRID },
                afterDataLimits: axisRange(span, lo, hi)
            });

            // Popisky časové osy na celé čtvrthodiny / hodiny (podle délky okna), max. ~7
            const timeTicks = (axis) => {
                const steps = [5, 10, 15, 30, 60, 120, 180, 240, 360].map((m) => m * 60000);
                const step = steps.find((s) => (axis.max - axis.min) / s <= 7) || steps[steps.length - 1];
                const offset = new Date(axis.min).getTimezoneOffset() * 60000;
                const ticks = [];
                for (let t = Math.ceil((axis.min - offset) / step) * step + offset; t <= axis.max; t += step) {
                    ticks.push({ value: t });
                }
                axis.ticks = ticks;
            };

            const hasData = (loc) => datasets.some((ds) => ds.loc === loc &&
                ds.data.some((v) => (v !== null && typeof v === 'object' ? v.y : v) != null));

            if (App.charts.climateChart) App.charts.climateChart.destroy();

            App.charts.climateChart = new Chart(canvas, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    locale: 'cs-CZ',
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: isDay ? {
                            type: 'linear',
                            min: xRange ? xRange.min : undefined,
                            max: xRange ? xRange.max : undefined,
                            bounds: 'data',
                            afterBuildTicks: timeTicks,
                            ticks: { callback: (v) => fmtTime(v), maxRotation: 0, autoSkip: false },
                            grid: { color: GRID }
                        } : {
                            type: 'category',
                            ticks: { maxRotation: 0, autoSkip: true },
                            grid: { color: GRID }
                        },
                        // Pořadí určuje umístění: osa uvedená později je v Chart.js výš.
                        // ySpacer je prázdná osa bez popisků = mezera mezi panely.
                        yHum: panel('Vlhkost (%)', 20, 0, 100),
                        ySpacer: {
                            type: 'linear', position: 'left', stack: 'climate', stackWeight: 0.12,
                            ticks: { display: false }, grid: { display: false }, border: { display: false }
                        },
                        yTemp: panel('Teplota (°C)', 10)
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            // Klik na „Kurník“ / „Venku“ skryje teplotu i vlhkost daného místa
                            onClick: (e, item, legend) => {
                                const chart = legend.chart;
                                const loc = chart.data.datasets[item.datasetIndex].loc;
                                const show = !chart.isDatasetVisible(item.datasetIndex);
                                chart.data.datasets.forEach((ds, i) => {
                                    if (ds.loc === loc) chart.setDatasetVisibility(i, show);
                                });
                                chart.update();
                            },
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                generateLabels: (chart) => PLACES.filter((p) => hasData(p.key)).map((p) => {
                                    const i = chart.data.datasets.findIndex((ds) => ds.loc === p.key && !ds.isMin);
                                    return {
                                        text: p.name,
                                        fillStyle: p.color,
                                        strokeStyle: p.color,
                                        lineWidth: 0,
                                        hidden: !chart.isDatasetVisible(i),
                                        datasetIndex: i
                                    };
                                })
                            }
                        },
                        tooltip: {
                            filter: (item) => !item.dataset.isMin,
                            callbacks: {
                                title: (items) => {
                                    if (!items.length) return '';
                                    return isDay ? fmtDateTime(items[0].parsed.x) : items[0].label;
                                },
                                label: (ctx) => {
                                    const ds = ctx.dataset;
                                    const m = METRICS.find((x) => x.key === ds.metric);
                                    const min = ds.minData ? ds.minData[ctx.dataIndex] : null;
                                    const value = min != null
                                        ? fmtNum(min) + ' – ' + fmtNum(ctx.parsed.y)
                                        : fmtNum(ctx.parsed.y);
                                    return ' ' + ds.label + ': ' + value + m.unit;
                                }
                            }
                        }
                    }
                }
            });
        },

        async loadEggChart(period) {
            period = period || 'week';
            const canvas = document.getElementById('chart-eggs');
            if (!canvas) return;

            let records, labels, values;

            if (period === 'year') {
                const data = await App.api.get('/api/eggs?group=month&months=12');
                if (!data) return;
                records = data.records || [];
                labels = records.map(r => {
                    const [y, m] = r.month.split('-');
                    const d = new Date(parseInt(y), parseInt(m) - 1);
                    return d.toLocaleDateString('cs-CZ', { month: 'short' });
                });
                values = records.map(r => parseInt(r.egg_count));
            } else {
                const daysMap = { week: 7, month: 30 };
                const days = daysMap[period] || 7;
                const data = await App.api.get('/api/eggs?days=' + days);
                if (!data) return;
                records = (data.records || []).reverse();
                labels = records.map(r => {
                    const d = new Date(r.record_date);
                    return d.toLocaleDateString('cs-CZ', { day: 'numeric', month: 'numeric' });
                });
                values = records.map(r => r.egg_count);
            }

            const showLabels = true;

            if (App.charts.eggChart) App.charts.eggChart.destroy();

            App.charts.eggChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Počet vajec',
                        data: values,
                        backgroundColor: (() => {
                            const max = Math.max(...values);
                            const min = Math.min(...values);
                            return values.map(v => {
                                if (max === min) return '#3498db';
                                if (v === max) return '#2ecc71';
                                if (v === min) return '#e74c3c';
                                return '#3498db';
                            });
                        })(),
                        borderRadius: 4
                    }]
                },
                plugins: showLabels ? [{
                    id: 'datalabels',
                    afterDatasetsDraw(chart) {
                        const { ctx } = chart;
                        chart.data.datasets.forEach((dataset, i) => {
                            const meta = chart.getDatasetMeta(i);
                            meta.data.forEach((bar, index) => {
                                const value = dataset.data[index];
                                if (value == null) return;
                                ctx.save();
                                ctx.font = 'bold 12px ' + (ctx.font.split(' ').pop() || 'sans-serif');
                                ctx.fillStyle = '#333';
                                ctx.textAlign = 'center';
                                ctx.fillText(value, bar.x, bar.y - 5);
                                ctx.restore();
                            });
                        });
                    }
                }] : [],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: showLabels ? 20 : 5 } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: period === 'year' ? undefined : 1 }
                        },
                        x: {
                            ticks: { autoSkip: false }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    },

    // --- Poznámky ---
    notes: {
        audioCache: {},
        loadingRowId: null,

        init() {
            const form = document.getElementById('note-form');
            if (form) {
                const dateInput = document.getElementById('note-date');
                if (dateInput && typeof flatpickr !== 'undefined') {
                    flatpickr(dateInput, {
                        locale: 'cs',
                        dateFormat: 'Y-m-d',
                        defaultDate: 'today'
                    });
                }

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(form);
                    const id = fd.get('id');
                    const content = fd.get('content').trim();
                    const noteDate = fd.get('note_date');
                    if (!content) return;

                    let result;
                    if (id) {
                        result = await App.api.post('/api/notes/update', { id: parseInt(id), content, note_date: noteDate });
                    } else {
                        result = await App.api.post('/api/notes', { content, note_date: noteDate });
                    }

                    if (!result) return;
                    if (result.error) { alert(result.error); return; }

                    App.notes.updateTable(result.note);
                    App.notes.hideForm();
                });
            }

            App.notes.initSpeech();
        },

        toggleForm() {
            const wrap = document.getElementById('note-form-wrap');
            const form = document.getElementById('note-form');
            if (!wrap) return;

            const isVisible = wrap.style.display !== 'none';
            if (isVisible) {
                App.notes.hideForm();
            } else {
                form.reset();
                form.querySelector('input[name="id"]').value = '';
                const dateInput = document.getElementById('note-date');
                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.setDate(new Date());
                }
                wrap.style.display = '';
                form.content.focus();
            }
        },

        hideForm() {
            const wrap = document.getElementById('note-form-wrap');
            const form = document.getElementById('note-form');
            if (wrap) wrap.style.display = 'none';
            if (form) form.reset();
        },

        edit(tr) {
            if (!tr) return;
            const form = document.getElementById('note-form');
            const wrap = document.getElementById('note-form-wrap');
            const dateInput = document.getElementById('note-date');
            if (!form || !wrap) return;

            form.querySelector('input[name="id"]').value = tr.dataset.id;
            if (dateInput) {
                dateInput.value = tr.dataset.date;
                if (dateInput._flatpickr) dateInput._flatpickr.setDate(tr.dataset.date);
            }
            form.content.value = tr.dataset.content;
            App.autoResize.resize(form.content);
            wrap.style.display = '';
            form.content.focus();
        },

        async remove(id) {
            if (!confirm('Smazat poznámku?')) return;
            App.notes.clearAudioState(String(id));
            const result = await App.api.post('/api/notes/delete', { id });
            if (!result) return;
            const row = document.querySelector(`#notes-table-body tr[data-id="${id}"]`);
            if (row) row.remove();
        },

        updateTable(note) {
            if (!note) return;
            const tbody = document.getElementById('notes-table-body');
            if (!tbody) return;

            const d = new Date(note.note_date);
            const dateStr = App.formatDate(d);
            const contentEsc = App.escapeHtml(note.content);

            const noteActionsHtml = window.__isLoggedIn ? `
                <td class="maintenance-actions">
                    <button class="btn-icon" onclick="App.notes.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                    <button class="btn-icon btn-icon--danger" onclick="App.notes.remove(${note.id})" title="Smazat">&times;</button>
                </td>` : '';
            const html = `<tr data-id="${note.id}" data-date="${note.note_date}" data-content="${contentEsc}">
                <td>${App.notes.renderContentCell(dateStr, contentEsc)}</td>
                ${noteActionsHtml}
            </tr>`;

            const existingRow = tbody.querySelector(`tr[data-id="${note.id}"]`);
            App.notes.clearAudioState(String(note.id));
            if (existingRow) {
                existingRow.outerHTML = html;
            } else {
                tbody.insertAdjacentHTML('afterbegin', html);
            }

            App.notes.refreshSpeechButtons();
        },

        initSpeech() {
            window.addEventListener('beforeunload', () => {
                Object.keys(App.notes.audioCache).forEach((rowId) => App.notes.clearAudioState(rowId));
            });
            App.notes.refreshSpeechButtons();
        },

        async toggleSpeech(tr) {
            if (!tr) return;

            const rowId = String(tr.dataset.id || '');
            if (!rowId) return;
            App.notes.loadingRowId = rowId;
            App.notes.refreshSpeechButtons();

            try {
                let audioState = App.notes.audioCache[rowId];
                if (audioState?.visible) {
                    App.notes.hideAudioPlayer(tr, rowId);
                    return;
                }

                let audioUrl = audioState?.url || null;
                if (!audioUrl) {
                    const result = await App.api.post('/api/notes/speech', { id: parseInt(rowId, 10) });
                    if (!result) return;
                    if (result.error) {
                        alert(result.error);
                        return;
                    }

                    audioUrl = App.notes.createAudioUrl(result.audio_base64, result.mime_type);
                    App.notes.audioCache[rowId] = { url: audioUrl, visible: false };
                }

                App.notes.showAudioPlayer(tr, rowId, audioUrl);
            } finally {
                if (App.notes.loadingRowId === rowId) {
                    App.notes.loadingRowId = null;
                    App.notes.refreshSpeechButtons();
                }
            }
        },

        createAudioUrl(base64, mimeType) {
            const binary = window.atob(base64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i += 1) {
                bytes[i] = binary.charCodeAt(i);
            }
            return URL.createObjectURL(new Blob([bytes], { type: mimeType || 'audio/mpeg' }));
        },

        showAudioPlayer(tr, rowId, audioUrl) {
            const audioWrap = tr.querySelector('.note-entry__audio');
            if (!audioWrap) return;

            audioWrap.innerHTML = `<audio controls preload="metadata" class="note-entry__player" src="${audioUrl}"></audio>`;
            audioWrap.hidden = false;
            const audio = audioWrap.querySelector('audio');
            if (audio) {
                audio.addEventListener('ended', () => App.notes.refreshSpeechButtons(), { once: true });
            }
            if (App.notes.audioCache[rowId]) {
                App.notes.audioCache[rowId].visible = true;
            }
            App.notes.refreshSpeechButtons();
        },

        hideAudioPlayer(tr, rowId) {
            const audioWrap = tr.querySelector('.note-entry__audio');
            if (audioWrap) {
                const player = audioWrap.querySelector('audio');
                if (player) player.pause();
                audioWrap.innerHTML = '';
                audioWrap.hidden = true;
            }
            if (App.notes.audioCache[rowId]) {
                App.notes.audioCache[rowId].visible = false;
            }
            App.notes.refreshSpeechButtons();
        },

        clearAudioState(rowId) {
            const key = String(rowId);
            const state = App.notes.audioCache[key];
            if (state?.url) {
                URL.revokeObjectURL(state.url);
            }
            delete App.notes.audioCache[key];
        },

        refreshSpeechButtons() {
            document.querySelectorAll('.note-entry__speak').forEach((btn) => {
                const tr = btn.closest('tr');
                const rowId = tr?.dataset?.id || '';
                const isLoading = !!tr && App.notes.loadingRowId === rowId;
                const isVisible = !!App.notes.audioCache[rowId]?.visible;
                btn.disabled = isLoading;
                btn.classList.toggle('is-speaking', isVisible);
                btn.textContent = isLoading ? 'Načítám...' : (isVisible ? 'Skrýt přehrávač' : 'Přehrát');
                btn.title = isLoading
                    ? 'Připravuji audio'
                    : (isVisible ? 'Skrýt audio přehrávač' : 'Načíst audio přehrávač');
            });
        },

        renderContentCell(dateStr, contentEsc) {
            return `<div class="note-entry">
                <div class="note-entry__main">
                    <span class="note-entry__date">${dateStr}</span>
                    <span class="note-entry__text">${contentEsc}</span>
                    <div class="note-entry__audio" hidden></div>
                </div>
                <button type="button" class="btn btn--outline btn--round btn--small note-entry__speak" onclick="App.notes.toggleSpeech(this.closest('tr'))" title="Přečíst záznam nahlas">Přehrát</button>
            </div>`;
        }
    },

    // --- Galerie ---
    gallery: {
        init() {
            const input = document.getElementById('photo-upload');
            const grid = document.getElementById('gallery-grid');

            // Klik na fotku otevře lightbox
            grid?.addEventListener('click', (e) => {
                const item = e.target.closest('.gallery-item');
                if (!item || e.target.closest('.gallery-item__delete')) return;
                const img = item.querySelector('img');
                if (img) {
                    // Zobrazit plnou verzi (ne thumbnail)
                    const fullSrc = img.src.replace('/foto/thumbs/', '/foto/');
                    App.gallery.openLightbox(fullSrc);
                }
            });

            // Zavřít lightbox klávesou Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') App.gallery.closeLightbox();
            });

            if (!input) return;

            input.addEventListener('change', async () => {
                const file = input.files[0];
                if (!file) return;

                const fd = new FormData();
                fd.append('photo', file);

                const result = await App.api.upload('/api/photos', fd);
                if (!result) return;
                if (result.error) { alert(result.error); return; }

                const photo = result.photo;
                if (grid && photo) {
                    const div = document.createElement('div');
                    div.className = 'gallery-item';
                    div.dataset.id = photo.id;
                    div.innerHTML = `<img src="/foto/thumbs/${App.escapeHtml(photo.filename)}"
                        alt="${App.escapeHtml(photo.caption || '')}" loading="lazy"
                        onerror="this.onerror=null;this.src='/foto/${App.escapeHtml(photo.filename)}'">`;
                    grid.prepend(div);
                }
                input.value = '';
            });
        },

        openLightbox(src) {
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            if (!lightbox || !img) return;
            img.src = src;
            lightbox.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        },

        closeLightbox() {
            const lightbox = document.getElementById('lightbox');
            if (!lightbox) return;
            lightbox.classList.remove('is-open');
            document.body.style.overflow = '';
        },

        async remove(id) {
            if (!confirm('Smazat fotku?')) return;
            const result = await App.api.post('/api/photos/delete', { id });
            if (!result) return;
            const item = document.querySelector(`#gallery-grid .gallery-item[data-id="${id}"]`);
            if (item) item.remove();
        }
    },

    // --- Počasí ---
    weather: {
        async init() {
            const container = document.getElementById('weather-content');
            if (!container) return;

            const data = await App.api.get('/api/weather');
            if (!data || data.error) {
                container.innerHTML = '<p class="text-muted">Předpověď není dostupná.</p>';
                return;
            }

            const forecast = data.forecast || [];
            if (forecast.length === 0) {
                container.innerHTML = '<p class="text-muted">Žádná data.</p>';
                return;
            }

            container.innerHTML = '<div class="weather-forecast">' + forecast.map(day => {
                const d = new Date(day.date + 'T12:00:00');
                const dateStr = d.toLocaleDateString('cs-CZ', { weekday: 'short', day: 'numeric', month: 'numeric' });
                const iconUrl = `https://openweathermap.org/img/wn/${day.icon}@4x.png`;
                const tempDay = day.temp_day ?? day.temp ?? '–';
                const tempNight = day.temp_night ?? '–';
                return `<div class="weather-card">
                    <span class="weather-card__date">${dateStr}</span>
                    <img class="weather-card__icon" src="${iconUrl}" alt="${App.escapeHtml(day.description)}" title="${App.escapeHtml(day.description)}">
                    <div class="weather-card__temps">
                        <span class="weather-card__temp weather-card__temp--day">${tempDay}°</span>
                        <span class="weather-card__temp weather-card__temp--night">${tempNight}°</span>
                    </div>
                </div>`;
            }).join('') + '</div>';
        }
    },

    // --- Livestream ---
    livestream: {
        toggle() {
            const content = document.getElementById('livestream-content');
            const btn = document.getElementById('livestream-toggle');
            if (!content || !btn) return;

            const isVisible = content.style.display !== 'none';

            if (isVisible) {
                // Sbalit – odebrat iframe (zastaví video)
                const iframe = content.querySelector('iframe');
                if (iframe) iframe.remove();
                content.style.display = 'none';
                btn.textContent = 'Rozbalit';
            } else {
                // Rozbalit – vrátit iframe
                const src = content.dataset.src;
                if (src) {
                    const wrap = content.querySelector('.livestream-wrap');
                    if (wrap) {
                        wrap.innerHTML = `<iframe src="${src}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
                    }
                }
                content.style.display = '';
                btn.textContent = 'Sbalit';
            }
        }
    },

    // --- Slepice ---
    chickens: {
        init() {
            App.chickens.initLightbox();

            const form = document.getElementById('chicken-form');
            if (!form) return;
            const ringColor = document.getElementById('ch-ring-color');
            const ringColorValue = document.getElementById('ch-ring-color-value');

            const syncRingColorValue = () => {
                if (ringColor && ringColorValue) {
                    ringColorValue.textContent = ringColor.value;
                }
            };

            ringColor?.addEventListener('input', syncRingColorValue);
            syncRingColorValue();

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const data = Object.fromEntries(fd.entries());
                const id = data.id;

                let result;
                if (id) {
                    result = await App.api.post('/api/chickens/update', data);
                } else {
                    delete data.id;
                    result = await App.api.post('/api/chickens', data);
                }

                if (!result) return;
                if (result.error) { alert(result.error); return; }

                // Refresh stránky – nejjednodušší po CRUD
                window.location.reload();
            });
        },

        setView(view) {
            const cards = document.getElementById('chickens-cards');
            const table = document.getElementById('chickens-table');
            if (!cards || !table) return;

            cards.style.display = view === 'cards' ? '' : 'none';
            table.style.display = view === 'table' ? '' : 'none';

            document.querySelectorAll('.view-toggle__btn').forEach(btn => {
                btn.classList.toggle('is-active', btn.dataset.view === view);
            });
        },

        showForm(data) {
            const wrap = document.getElementById('chicken-form-wrap');
            const form = document.getElementById('chicken-form');
            const title = document.getElementById('chicken-form-title');
            if (!wrap || !form) return;

            form.reset();
            form.id_field?.remove();

            if (data) {
                title.textContent = 'Upravit slepici';
                form.name.value = data.name || '';
                form.breed.value = data.breed || '';
                form.color.value = data.color || '';
                form.ring_color.value = data.ring_color || '#f2c94c';
                document.getElementById('ch-ring-color-value').textContent = form.ring_color.value;
                form.status.value = data.status || 'active';
                form.birth_date.value = data.birth_date || '';
                form.acquired_date.value = data.acquired_date || '';
                form.end_date.value = data.end_date || '';
                form.note.value = data.note || '';
                App.autoResize.resize(form.note);
                form.querySelector('input[name="id"]').value = data.id;
            } else {
                title.textContent = 'Přidat slepici';
                form.querySelector('input[name="id"]').value = '';
                form.ring_color.value = '#f2c94c';
                document.getElementById('ch-ring-color-value').textContent = form.ring_color.value;
            }

            wrap.style.display = '';
            form.name.focus();
        },

        hideForm() {
            const wrap = document.getElementById('chicken-form-wrap');
            if (wrap) wrap.style.display = 'none';
        },

        edit(id) {
            const data = (window.__chickensData || []).find(c => c.id == id);
            if (data) App.chickens.showForm(data);
        },

        initLightbox() {
            document.querySelectorAll('.chicken-card__photo img').forEach((img) => {
                img.addEventListener('click', (e) => {
                    if (e.target.closest('.chicken-card__upload-btn')) return;
                    App.gallery.openLightbox(img.src);
                });
            });
        },

        async remove(id) {
            if (!confirm('Smazat slepici?')) return;
            const result = await App.api.post('/api/chickens/delete', { id });
            if (!result || result.error) return;
            window.location.reload();
        },

        async uploadPhoto(id, input) {
            const file = input.files[0];
            if (!file) return;

            const fd = new FormData();
            fd.append('id', id);
            fd.append('photo', file);

            const result = await App.api.upload('/api/chickens/photo', fd);
            if (!result || result.error) {
                if (result?.error) alert(result.error);
                return;
            }

            // Refresh
            window.location.reload();
        }
    },

    // --- Údržba ---
    maintenance: {
        config: {
            bedding: {
                apiBase: '/api/bedding',
                dateField: 'changed_at',
                formId: 'bedding-form',
                formWrapId: 'bedding-form-wrap',
                dateInputId: 'bedding-date',
                tbodyId: 'bedding-table-body'
            },
            repair: {
                apiBase: '/api/repairs',
                dateField: 'repaired_at',
                formId: 'repair-form',
                formWrapId: 'repair-form-wrap',
                dateInputId: 'repair-date',
                tbodyId: 'repair-table-body'
            }
        },

        init() {
            ['bedding', 'repair'].forEach(type => {
                const cfg = App.maintenance.config[type];
                const form = document.getElementById(cfg.formId);
                if (!form) return;

                const dateInput = document.getElementById(cfg.dateInputId);
                if (dateInput && typeof flatpickr !== 'undefined') {
                    flatpickr(dateInput, {
                        locale: 'cs',
                        dateFormat: 'Y-m-d',
                        defaultDate: new Date()
                    });
                }

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(form);
                    const id = fd.get('id');
                    const data = {};
                    data[cfg.dateField] = fd.get(cfg.dateField);
                    data.note = fd.get('note') || '';

                    let result;
                    if (id) {
                        data.id = parseInt(id);
                        result = await App.api.post(cfg.apiBase + '/update', data);
                    } else {
                        result = await App.api.post(cfg.apiBase, data);
                    }

                    if (!result) return;
                    if (result.error) { alert(result.error); return; }

                    App.maintenance.updateTable(type, result.record);
                    App.maintenance.hideForm(type);

                    if (type === 'bedding') {
                        App.maintenance.refreshBeddingStatus();
                    }
                });
            });

            // Interval form
            const intervalForm = document.getElementById('bedding-interval-form');
            if (intervalForm) {
                intervalForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const days = parseInt(document.getElementById('bedding-interval-days').value) || 0;
                    const result = await App.api.post('/api/bedding/interval', { interval_days: days });
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    App.maintenance.updateBeddingStatus(result.last_change, result.next_change, result.interval_days);
                    document.getElementById('bedding-interval-wrap').style.display = 'none';
                });
            }
        },

        toggleInterval() {
            const wrap = document.getElementById('bedding-interval-wrap');
            if (!wrap) return;
            wrap.style.display = wrap.style.display === 'none' ? '' : 'none';
        },

        async beddingQuickLog() {
            const result = await App.api.post('/api/bedding/quick-log', { note: 'Podestýlka vyměněna' });
            if (!result) return;
            if (result.error) { alert(result.error); return; }

            App.maintenance.updateTable('bedding', result.record);
            App.maintenance.updateBeddingStatus(result.record.changed_at, result.next_change, result.interval_days);
        },

        refreshBeddingStatus() {
            // Re-calculate from the first row in the table (latest record)
            const tbody = document.getElementById('bedding-table-body');
            const intervalInput = document.getElementById('bedding-interval-days');
            if (!tbody || !intervalInput) return;

            const firstRow = tbody.querySelector('tr');
            if (!firstRow) return;

            const lastDatetime = firstRow.dataset.datetime;
            const intervalDays = parseInt(intervalInput.value) || 14;
            const lastDate = new Date(lastDatetime.replace(' ', 'T'));
            const nextDate = new Date(lastDate);
            nextDate.setDate(nextDate.getDate() + intervalDays);
            const nextStr = nextDate.toISOString().split('T')[0];

            App.maintenance.updateBeddingStatus(lastDatetime, nextStr, intervalDays);
        },

        updateBeddingStatus(lastChange, nextChange, intervalDays) {
            const lastEl = document.getElementById('bedding-last-date');
            const nextEl = document.getElementById('bedding-next-date');

            if (lastEl && lastChange) {
                const d = new Date(lastChange.replace(' ', 'T'));
                lastEl.textContent = App.formatDateTime(d);
            }

            if (nextEl && nextChange) {
                const next = new Date(nextChange + 'T00:00:00');
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const diffMs = next - today;
                const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

                const dateStr = App.formatDate(next);
                let label = '';
                if (diffDays < 0) {
                    label = ` (${App.dnyText(diffDays)} po term\u00ednu)`;
                } else if (diffDays === 0) {
                    label = ' (dnes)';
                } else {
                    label = ` (za ${App.dnyText(diffDays)})`;
                }

                nextEl.innerHTML = dateStr + ' <small>' + App.escapeHtml(label) + '</small>';

                // Update CSS class
                nextEl.classList.remove('bedding-status__value--ok', 'bedding-status__value--warning', 'bedding-status__value--overdue');
                if (diffDays < 0) {
                    nextEl.classList.add('bedding-status__value--overdue');
                } else if (diffDays <= 3) {
                    nextEl.classList.add('bedding-status__value--warning');
                } else {
                    nextEl.classList.add('bedding-status__value--ok');
                }
            }

            // Also update dashboard widget if present
            App.maintenance.updateDashboardWidget(lastChange, nextChange);
        },

        updateDashboardWidget(lastChange, nextChange) {
            // Update last change on dashboard
            const lastWidget = document.getElementById('dashboard-bedding-last');
            if (lastWidget && lastChange) {
                const d = new Date(lastChange.replace(' ', 'T'));
                lastWidget.textContent = App.formatDate(d);
            }

            const widget = document.getElementById('dashboard-bedding-next');
            if (!widget || !nextChange) return;

            const next = new Date(nextChange + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const diffDays = Math.round((next - today) / (1000 * 60 * 60 * 24));

            let label = '';
            if (diffDays < 0) {
                label = `${App.dnyText(diffDays)} po term\u00ednu`;
            } else if (diffDays === 0) {
                label = 'dnes';
            } else {
                label = `za ${App.dnyText(diffDays)}`;
            }

            widget.textContent = label;

            widget.classList.remove('bedding-status__value--ok', 'bedding-status__value--warning', 'bedding-status__value--overdue');
            if (diffDays < 0) {
                widget.classList.add('bedding-status__value--overdue');
            } else if (diffDays <= 3) {
                widget.classList.add('bedding-status__value--warning');
            } else {
                widget.classList.add('bedding-status__value--ok');
            }
        },

        toggleForm(type) {
            const cfg = App.maintenance.config[type];
            const wrap = document.getElementById(cfg.formWrapId);
            const form = document.getElementById(cfg.formId);
            if (!wrap) return;

            const isVisible = wrap.style.display !== 'none';
            if (isVisible) {
                App.maintenance.hideForm(type);
            } else {
                form.reset();
                form.querySelector('input[name="id"]').value = '';
                const dateInput = document.getElementById(cfg.dateInputId);
                if (dateInput && dateInput._flatpickr) {
                    dateInput._flatpickr.setDate(new Date());
                }
                form.querySelector('button[type="submit"]').textContent = 'Uložit';
                wrap.style.display = '';
            }
        },

        hideForm(type) {
            const cfg = App.maintenance.config[type];
            const wrap = document.getElementById(cfg.formWrapId);
            const form = document.getElementById(cfg.formId);
            if (wrap) wrap.style.display = 'none';
            if (form) form.reset();
        },

        edit(type, tr) {
            if (!tr) return;
            const cfg = App.maintenance.config[type];
            const form = document.getElementById(cfg.formId);
            const wrap = document.getElementById(cfg.formWrapId);
            const dateInput = document.getElementById(cfg.dateInputId);
            if (!form || !wrap) return;

            form.querySelector('input[name="id"]').value = tr.dataset.id;
            dateInput.value = tr.dataset.datetime;
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(tr.dataset.datetime);
            const noteTa = form.querySelector('[name="note"]');
            noteTa.value = tr.dataset.note;
            App.autoResize.resize(noteTa);
            form.querySelector('button[type="submit"]').textContent = 'Uložit';
            wrap.style.display = '';
        },

        async remove(type, id) {
            if (!confirm('Smazat záznam?')) return;
            const cfg = App.maintenance.config[type];
            const result = await App.api.post(cfg.apiBase + '/delete', { id });
            if (!result || result.error) return;

            const row = document.querySelector(`#${cfg.tbodyId} tr[data-id="${id}"]`);
            if (row) row.remove();

            if (type === 'bedding') {
                App.maintenance.refreshBeddingStatus();
            }
        },

        updateTable(type, record) {
            if (!record) return;
            const cfg = App.maintenance.config[type];
            const tbody = document.getElementById(cfg.tbodyId);
            if (!tbody) return;

            const datetime = record[cfg.dateField];
            const d = new Date(datetime.replace(' ', 'T'));
            const dateStr = App.formatDate(d);
            const noteEsc = App.escapeHtml(record.note || '');

            const maintActionsHtml = window.__isLoggedIn ? `
                <td class="maintenance-actions">
                    <button class="btn-icon" onclick="App.maintenance.edit('${type}', this.closest('tr'))" title="Upravit">&#x270E;</button>
                    <button class="btn-icon btn-icon--danger" onclick="App.maintenance.remove('${type}', ${record.id})" title="Smazat">&times;</button>
                </td>` : '';
            const html = `<tr data-id="${record.id}" data-datetime="${datetime}" data-note="${noteEsc}">
                <td>${dateStr}</td>
                <td>${noteEsc}</td>
                ${maintActionsHtml}
            </tr>`;

            const existingRow = tbody.querySelector(`tr[data-id="${record.id}"]`);
            if (existingRow) {
                existingRow.outerHTML = html;
            } else {
                tbody.insertAdjacentHTML('afterbegin', html);
            }

            // Seřadit řádky podle data sestupně
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const da = a.dataset.datetime || '';
                const db = b.dataset.datetime || '';
                return db.localeCompare(da);
            });
            rows.forEach(r => tbody.appendChild(r));
        }
    },

    // --- Helpers ---
    dnyText(n) {
        const abs = Math.abs(n);
        if (abs === 1) return abs + ' den';
        if (abs >= 2 && abs <= 4) return abs + ' dny';
        return abs + ' dn\u00ed';
    },

    pad(n) { return n < 10 ? '0' + n : '' + n; },

    formatDate(d) {
        return App.pad(d.getDate()) + '.' + App.pad(d.getMonth() + 1) + '.' + d.getFullYear();
    },

    formatDateTime(d) {
        return App.formatDate(d) + ' ' + App.pad(d.getHours()) + ':' + App.pad(d.getMinutes());
    },

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // --- Krmení ---
    feeding: {
        charts: { consumption: null, cost: null },
        typeColors: ['#e8b730', '#5a8f3c', '#3a7bd5', '#e74c3c', '#9b59b6', '#1abc9c', '#f39c12', '#2c3e50'],

        init() {
            if (!document.getElementById('feeding-record-form')) return;

            // Init flatpickr on date inputs
            ['feeding-record-date', 'feeding-purchase-date'].forEach(id => {
                const el = document.getElementById(id);
                if (el && typeof flatpickr !== 'undefined') {
                    flatpickr(el, { locale: 'cs', dateFormat: 'Y-m-d', defaultDate: new Date() });
                }
            });

            // Record form
            const recordForm = document.getElementById('feeding-record-form');
            if (recordForm) {
                recordForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(recordForm);
                    const data = {
                        feed_type_id: parseInt(fd.get('feed_type_id')),
                        record_date: fd.get('record_date'),
                        amount_kg: parseFloat(fd.get('amount_kg')),
                        note: fd.get('note') || ''
                    };
                    const id = fd.get('id');
                    let result;
                    if (id) {
                        data.id = parseInt(id);
                        result = await App.api.post('/api/feeding/records/update', data);
                    } else {
                        result = await App.api.post('/api/feeding/records', data);
                    }
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    window.location.reload();
                });
            }

            // Type form
            const typeForm = document.getElementById('feeding-type-form');
            if (typeForm) {
                typeForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(typeForm);
                    const data = {
                        name: fd.get('name'),
                        price_per_kg: parseFloat(fd.get('price_per_kg')) || 0,
                        palatability: fd.get('palatability') || '',
                        note: fd.get('note') || ''
                    };
                    const id = fd.get('id');
                    let result;
                    if (id) {
                        data.id = parseInt(id);
                        result = await App.api.post('/api/feeding/types/update', data);
                    } else {
                        result = await App.api.post('/api/feeding/types', data);
                    }
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    window.location.reload();
                });
            }

            // Purchase form
            const purchaseForm = document.getElementById('feeding-purchase-form');
            if (purchaseForm) {
                purchaseForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(purchaseForm);
                    const data = {
                        feed_type_id: parseInt(fd.get('feed_type_id')),
                        purchased_at: fd.get('purchased_at'),
                        quantity_kg: parseFloat(fd.get('quantity_kg')),
                        total_price: parseFloat(fd.get('total_price')),
                        note: fd.get('note') || ''
                    };
                    const result = await App.api.post('/api/feeding/purchases', data);
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    window.location.reload();
                });
            }

            // Period toggles for consumption chart
            const consumptionToggle = document.getElementById('consumption-period-toggle');
            if (consumptionToggle) {
                consumptionToggle.addEventListener('click', (e) => {
                    const btn = e.target.closest('.period-toggle__btn');
                    if (!btn) return;
                    consumptionToggle.querySelectorAll('.period-toggle__btn').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    App.feeding.loadConsumptionChart(btn.dataset.period);
                });
            }

            // Period toggles for cost chart
            const costToggle = document.getElementById('cost-period-toggle');
            if (costToggle) {
                costToggle.addEventListener('click', (e) => {
                    const btn = e.target.closest('.period-toggle__btn');
                    if (!btn) return;
                    costToggle.querySelectorAll('.period-toggle__btn').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    App.feeding.loadCostChart(parseInt(btn.dataset.period));
                });
            }

            // Load initial charts
            App.feeding.loadConsumptionChart('week');
            App.feeding.loadCostChart(12);
        },

        toggleForm(type) {
            const wrapId = `feeding-${type}-form-wrap`;
            const formId = `feeding-${type}-form`;
            const wrap = document.getElementById(wrapId);
            const form = document.getElementById(formId);
            if (!wrap) return;

            const isVisible = wrap.style.display !== 'none';
            if (isVisible) {
                App.feeding.hideForm(type);
            } else {
                form.reset();
                form.querySelector('input[name="id"]').value = '';
                const dateId = type === 'record' ? 'feeding-record-date' : (type === 'purchase' ? 'feeding-purchase-date' : null);
                if (dateId) {
                    const dateInput = document.getElementById(dateId);
                    if (dateInput && dateInput._flatpickr) dateInput._flatpickr.setDate(new Date());
                }
                wrap.style.display = '';
            }
        },

        hideForm(type) {
            const wrap = document.getElementById(`feeding-${type}-form-wrap`);
            const form = document.getElementById(`feeding-${type}-form`);
            if (wrap) wrap.style.display = 'none';
            if (form) form.reset();
        },

        editRecord(tr) {
            if (!tr) return;
            const form = document.getElementById('feeding-record-form');
            const wrap = document.getElementById('feeding-record-form-wrap');
            if (!form || !wrap) return;

            form.querySelector('input[name="id"]').value = tr.dataset.id;
            const dateInput = document.getElementById('feeding-record-date');
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(tr.dataset.date);
            else dateInput.value = tr.dataset.date;
            form.querySelector('select[name="feed_type_id"]').value = tr.dataset.feedTypeId;
            form.querySelector('input[name="amount_kg"]').value = tr.dataset.amount;
            { const ta = form.querySelector('[name="note"]'); ta.value = tr.dataset.note; App.autoResize.resize(ta); }
            wrap.style.display = '';
        },

        editType(tr) {
            if (!tr) return;
            const form = document.getElementById('feeding-type-form');
            const wrap = document.getElementById('feeding-type-form-wrap');
            if (!form || !wrap) return;

            form.querySelector('input[name="id"]').value = tr.dataset.id;
            form.querySelector('input[name="name"]').value = tr.dataset.name;
            form.querySelector('input[name="price_per_kg"]').value = tr.dataset.price;
            form.querySelector('select[name="palatability"]').value = tr.dataset.palatability;
            { const ta = form.querySelector('[name="note"]'); ta.value = tr.dataset.note; App.autoResize.resize(ta); }
            wrap.style.display = '';
        },

        async removeRecord(id) {
            if (!confirm('Smazat záznam krmení?')) return;
            const result = await App.api.post('/api/feeding/records/delete', { id });
            if (!result || result.error) return;
            window.location.reload();
        },

        async removeType(id) {
            if (!confirm('Smazat typ krmiva? Budou smazány i všechny související záznamy.')) return;
            const result = await App.api.post('/api/feeding/types/delete', { id });
            if (!result || result.error) return;
            window.location.reload();
        },

        async removePurchase(id) {
            if (!confirm('Smazat záznam nákupu?')) return;
            const result = await App.api.post('/api/feeding/purchases/delete', { id });
            if (!result || result.error) return;
            window.location.reload();
        },

        async loadConsumptionChart(period) {
            let url;
            if (period === 'week') {
                url = '/api/feeding/records?group=daily_by_type&days=7';
            } else if (period === 'month') {
                url = '/api/feeding/records?group=daily_by_type&days=30';
            } else {
                url = '/api/feeding/records?group=monthly_by_type&months=12';
            }

            const data = await App.api.get(url);
            if (!data || !data.records) return;

            const canvas = document.getElementById('chart-feeding-consumption');
            if (!canvas) return;

            // Build datasets per feed type
            const records = data.records;
            const typeMap = {};
            const labelsSet = new Set();

            records.forEach(r => {
                const key = period === 'year' ? r.month : r.record_date;
                labelsSet.add(key);
                if (!typeMap[r.feed_type_id]) {
                    typeMap[r.feed_type_id] = { name: r.feed_type_name, data: {} };
                }
                typeMap[r.feed_type_id].data[key] = parseFloat(r.total_kg);
            });

            const sortedLabels = Array.from(labelsSet).sort();
            const displayLabels = sortedLabels.map(l => {
                if (period === 'year') {
                    const [y, m] = l.split('-');
                    return m + '/' + y;
                }
                const d = new Date(l + 'T00:00:00');
                return App.pad(d.getDate()) + '.' + App.pad(d.getMonth() + 1) + '.';
            });

            const datasets = [];
            let colorIdx = 0;
            Object.keys(typeMap).forEach(typeId => {
                const t = typeMap[typeId];
                const color = App.feeding.typeColors[colorIdx % App.feeding.typeColors.length];
                datasets.push({
                    label: t.name,
                    data: sortedLabels.map(l => t.data[l] || 0),
                    backgroundColor: color,
                    borderColor: color,
                    borderWidth: 1
                });
                colorIdx++;
            });

            if (App.feeding.charts.consumption) {
                App.feeding.charts.consumption.destroy();
            }

            App.feeding.charts.consumption = new Chart(canvas, {
                type: 'bar',
                data: { labels: displayLabels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' kg'
                            }
                        }
                    },
                    scales: {
                        x: { stacked: true },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            title: { display: true, text: 'kg' }
                        }
                    }
                }
            });
        },

        async loadCostChart(months) {
            const data = await App.api.get('/api/feeding/stats?group=month&months=' + months);
            if (!data) return;

            const canvas = document.getElementById('chart-feeding-cost');
            if (!canvas) return;

            const consumption = data.consumption || [];
            const spending = data.spending || [];

            // Merge labels
            const allMonths = new Set();
            consumption.forEach(r => allMonths.add(r.month));
            spending.forEach(r => allMonths.add(r.month));
            const sortedMonths = Array.from(allMonths).sort();

            const displayLabels = sortedMonths.map(m => {
                const [y, mo] = m.split('-');
                return mo + '/' + y;
            });

            const consumptionMap = {};
            consumption.forEach(r => { consumptionMap[r.month] = parseFloat(r.total_cost); });
            const spendingMap = {};
            spending.forEach(r => { spendingMap[r.month] = parseFloat(r.total_spent); });

            if (App.feeding.charts.cost) {
                App.feeding.charts.cost.destroy();
            }

            App.feeding.charts.cost = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: displayLabels,
                    datasets: [
                        {
                            label: 'Odhadované náklady (spotřeba)',
                            data: sortedMonths.map(m => consumptionMap[m] || 0),
                            borderColor: '#e8b730',
                            backgroundColor: 'rgba(232, 183, 48, 0.1)',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Skutečné nákupy',
                            data: sortedMonths.map(m => spendingMap[m] || 0),
                            borderColor: '#5a8f3c',
                            backgroundColor: 'rgba(90, 143, 60, 0.1)',
                            fill: true,
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(0) + ' Kč'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Kč' }
                        }
                    }
                }
            });
        }
    },

    // --- Finance ---
    finance: {
        charts: { monthly: null, category: null },
        categoryColors: {
            feed: '#e8b730',
            bedding: '#8B6914',
            vet: '#e74c3c',
            equipment: '#3a7bd5',
            other: '#9b59b6'
        },
        categoryLabels: {
            feed: 'Krmivo',
            bedding: 'Podestýlka',
            vet: 'Veterina',
            equipment: 'Vybavení',
            other: 'Ostatní'
        },

        init() {
            if (!document.getElementById('finance-expense-form')) return;

            // Flatpickr
            ['finance-expense-date', 'finance-egg-tx-date'].forEach(id => {
                const el = document.getElementById(id);
                if (el && typeof flatpickr !== 'undefined') {
                    flatpickr(el, { locale: 'cs', dateFormat: 'Y-m-d', defaultDate: new Date() });
                }
            });

            // Expense form
            const expenseForm = document.getElementById('finance-expense-form');
            if (expenseForm) {
                expenseForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(expenseForm);
                    const data = {
                        expense_date: fd.get('expense_date'),
                        category: fd.get('category'),
                        amount: parseFloat(fd.get('amount')),
                        note: fd.get('note') || ''
                    };
                    const id = fd.get('id');
                    let result;
                    if (id) {
                        data.id = parseInt(id);
                        result = await App.api.post('/api/finance/expenses/update', data);
                    } else {
                        result = await App.api.post('/api/finance/expenses', data);
                    }
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    window.location.reload();
                });
            }

            // Egg transaction form
            const eggTxForm = document.getElementById('finance-egg-tx-form');
            if (eggTxForm) {
                eggTxForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(eggTxForm);
                    const data = {
                        transaction_date: fd.get('transaction_date'),
                        type: fd.get('type'),
                        quantity: parseInt(fd.get('quantity')),
                        price_total: parseFloat(fd.get('price_total')) || 0,
                        recipient: fd.get('recipient') || '',
                        note: fd.get('note') || ''
                    };
                    const id = fd.get('id');
                    let result;
                    if (id) {
                        data.id = parseInt(id);
                        result = await App.api.post('/api/finance/egg-transactions/update', data);
                    } else {
                        result = await App.api.post('/api/finance/egg-transactions', data);
                    }
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    window.location.reload();
                });

                // Hide price field for gifts
                const typeSelect = document.getElementById('finance-egg-tx-type');
                const priceInput = document.getElementById('finance-egg-tx-price');
                if (typeSelect && priceInput) {
                    typeSelect.addEventListener('change', () => {
                        priceInput.style.display = typeSelect.value === 'gift' ? 'none' : '';
                        if (typeSelect.value === 'gift') priceInput.value = '0';
                    });
                }
            }

            // Egg market price form
            const eggPriceForm = document.getElementById('egg-price-form');
            if (eggPriceForm) {
                eggPriceForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const fd = new FormData(eggPriceForm);
                    const result = await App.api.post('/api/finance/egg-market-price', {
                        egg_market_price: parseFloat(fd.get('egg_market_price'))
                    });
                    if (!result) return;
                    if (result.error) { alert(result.error); return; }
                    window.location.reload();
                });
            }

            // Period toggle
            const periodToggle = document.getElementById('finance-period-toggle');
            if (periodToggle) {
                periodToggle.addEventListener('click', (e) => {
                    const btn = e.target.closest('.period-toggle__btn');
                    if (!btn) return;
                    periodToggle.querySelectorAll('.period-toggle__btn').forEach(b => b.classList.remove('is-active'));
                    btn.classList.add('is-active');
                    App.finance.loadMonthlyChart(parseInt(btn.dataset.period));
                });
            }

            // Load charts
            App.finance.loadMonthlyChart(12);
            App.finance.loadCategoryChart();
        },

        toggleForm(type) {
            const wrapId = `finance-${type}-form-wrap`;
            const formId = `finance-${type}-form`;
            const wrap = document.getElementById(wrapId);
            const form = document.getElementById(formId);
            if (!wrap) return;

            const isVisible = wrap.style.display !== 'none';
            if (isVisible) {
                App.finance.hideForm(type);
            } else {
                form.reset();
                form.querySelector('input[name="id"]').value = '';
                const dateId = type === 'expense' ? 'finance-expense-date' : 'finance-egg-tx-date';
                const dateInput = document.getElementById(dateId);
                if (dateInput && dateInput._flatpickr) dateInput._flatpickr.setDate(new Date());
                wrap.style.display = '';
            }
        },

        hideForm(type) {
            const wrap = document.getElementById(`finance-${type}-form-wrap`);
            const form = document.getElementById(`finance-${type}-form`);
            if (wrap) wrap.style.display = 'none';
            if (form) form.reset();
        },

        toggleEggPriceForm() {
            const wrap = document.getElementById('egg-price-form-wrap');
            if (wrap) wrap.style.display = wrap.style.display === 'none' ? '' : 'none';
        },

        editExpense(tr) {
            if (!tr) return;
            const form = document.getElementById('finance-expense-form');
            const wrap = document.getElementById('finance-expense-form-wrap');
            if (!form || !wrap) return;

            form.querySelector('input[name="id"]').value = tr.dataset.id;
            const dateInput = document.getElementById('finance-expense-date');
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(tr.dataset.date);
            else dateInput.value = tr.dataset.date;
            form.querySelector('select[name="category"]').value = tr.dataset.category;
            form.querySelector('input[name="amount"]').value = tr.dataset.amount;
            { const ta = form.querySelector('[name="note"]'); ta.value = tr.dataset.note; App.autoResize.resize(ta); }
            wrap.style.display = '';
        },

        editEggTransaction(tr) {
            if (!tr) return;
            const form = document.getElementById('finance-egg-tx-form');
            const wrap = document.getElementById('finance-egg-tx-form-wrap');
            if (!form || !wrap) return;

            form.querySelector('input[name="id"]').value = tr.dataset.id;
            const dateInput = document.getElementById('finance-egg-tx-date');
            if (dateInput._flatpickr) dateInput._flatpickr.setDate(tr.dataset.date);
            else dateInput.value = tr.dataset.date;
            form.querySelector('select[name="type"]').value = tr.dataset.type;
            form.querySelector('input[name="quantity"]').value = tr.dataset.quantity;
            form.querySelector('input[name="price_total"]').value = tr.dataset.price;
            form.querySelector('input[name="recipient"]').value = tr.dataset.recipient;
            { const ta = form.querySelector('[name="note"]'); ta.value = tr.dataset.note; App.autoResize.resize(ta); }

            // Show/hide price field based on type
            const priceInput = document.getElementById('finance-egg-tx-price');
            if (priceInput) {
                priceInput.style.display = tr.dataset.type === 'gift' ? 'none' : '';
            }

            wrap.style.display = '';
        },

        async removeExpense(id) {
            if (!confirm('Smazat záznam nákladu?')) return;
            const result = await App.api.post('/api/finance/expenses/delete', { id });
            if (!result || result.error) return;
            window.location.reload();
        },

        async removeEggTransaction(id) {
            if (!confirm('Smazat záznam?')) return;
            const result = await App.api.post('/api/finance/egg-transactions/delete', { id });
            if (!result || result.error) return;
            window.location.reload();
        },

        async loadMonthlyChart(months) {
            const data = await App.api.get('/api/finance/summary?months=' + months);
            if (!data) return;

            const canvas = document.getElementById('chart-finance-monthly');
            if (!canvas) return;

            // Merge all months
            const allMonths = new Set();
            (data.feedMonthly || []).forEach(r => allMonths.add(r.month));
            (data.expenseMonthly || []).forEach(r => allMonths.add(r.month));
            (data.revenueMonthly || []).forEach(r => allMonths.add(r.month));
            const sorted = Array.from(allMonths).sort();

            const labels = sorted.map(m => {
                const [y, mo] = m.split('-');
                return mo + '/' + y;
            });

            const feedMap = {};
            (data.feedMonthly || []).forEach(r => { feedMap[r.month] = parseFloat(r.total_spent); });
            const expMap = {};
            (data.expenseMonthly || []).forEach(r => { expMap[r.month] = parseFloat(r.total); });
            const revMap = {};
            (data.revenueMonthly || []).forEach(r => { revMap[r.month] = parseFloat(r.revenue); });

            if (App.finance.charts.monthly) App.finance.charts.monthly.destroy();

            App.finance.charts.monthly = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Krmivo',
                            data: sorted.map(m => -(feedMap[m] || 0)),
                            backgroundColor: '#e8b730'
                        },
                        {
                            label: 'Ostatní náklady',
                            data: sorted.map(m => -(expMap[m] || 0)),
                            backgroundColor: '#e74c3c'
                        },
                        {
                            label: 'Příjmy z vajec',
                            data: sorted.map(m => revMap[m] || 0),
                            backgroundColor: '#27ae60'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + Math.abs(ctx.parsed.y).toFixed(0) + ' Kč'
                            }
                        }
                    },
                    scales: {
                        x: { stacked: true },
                        y: {
                            stacked: true,
                            title: { display: true, text: 'Kč' }
                        }
                    }
                }
            });
        },

        async loadCategoryChart() {
            const data = await App.api.get('/api/finance/summary?months=12');
            if (!data) return;

            const canvas = document.getElementById('chart-finance-category');
            if (!canvas) return;

            const categories = [];
            const amounts = [];
            const colors = [];

            // Add feed as first category
            if (data.feedTotal > 0) {
                categories.push('Krmivo');
                amounts.push(parseFloat(data.feedTotal));
                colors.push(App.finance.categoryColors.feed);
            }

            // Add expense categories
            (data.expensesByCategory || []).forEach(r => {
                categories.push(App.finance.categoryLabels[r.category] || r.category);
                amounts.push(parseFloat(r.total));
                colors.push(App.finance.categoryColors[r.category] || '#999');
            });

            if (App.finance.charts.category) App.finance.charts.category.destroy();

            App.finance.charts.category = new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: categories,
                    datasets: [{
                        data: amounts,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                label: ctx => {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                    return ctx.label + ': ' + ctx.parsed.toFixed(0) + ' Kč (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    },

    // --- Hamburger menu ---
    hamburger: {
        init() {
            const btn = document.getElementById('hamburger-btn');
            const nav = document.getElementById('site-nav');
            if (!btn || !nav) return;

            btn.addEventListener('click', () => {
                const isOpen = nav.classList.toggle('is-open');
                btn.classList.toggle('is-open', isOpen);
                btn.setAttribute('aria-expanded', isOpen);
            });

            document.addEventListener('click', (e) => {
                if (!btn.contains(e.target) && !nav.contains(e.target)) {
                    nav.classList.remove('is-open');
                    btn.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                }
            });

            // Dropdown submenu
            document.querySelectorAll('.site-nav__dropdown-toggle').forEach(toggle => {
                const dropdown = toggle.closest('.site-nav__dropdown');
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    document.querySelectorAll('.site-nav__dropdown.is-open').forEach(d => {
                        if (d !== dropdown) d.classList.remove('is-open');
                    });
                    dropdown.classList.toggle('is-open');
                });
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.site-nav__dropdown')) {
                    document.querySelectorAll('.site-nav__dropdown.is-open').forEach(d => {
                        d.classList.remove('is-open');
                    });
                }
            });
        }
    },

    // --- Vtipy o slepicích ---
    jokes: {
        init() {
            const form = document.getElementById('joke-form');
            if (!form) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const id = fd.get('id');
                const content = fd.get('content').trim();
                if (!content) return;

                let result;
                if (id) {
                    result = await App.api.post('/api/snippets/update', { id: parseInt(id), content, type: 'joke' });
                } else {
                    result = await App.api.post('/api/snippets', { content, type: 'joke' });
                }

                if (!result) return;
                if (result.error) { alert(result.error); return; }

                App.jokes.showJoke(result.snippet.content);
                App.jokes.hideForm();
            });
        },

        async loadRandom() {
            const result = await App.api.get('/api/snippets/random?type=joke');
            if (!result) return;
            if (result.snippet) {
                App.jokes.showJoke(result.snippet.content);
            }
        },

        showJoke(text) {
            const el = document.getElementById('joke-text');
            if (!el) return;
            el.textContent = text;
            el.classList.remove('joke-text--empty');
        },

        toggleForm() {
            const wrap = document.getElementById('joke-form-wrap');
            const form = document.getElementById('joke-form');
            if (!wrap) return;

            if (wrap.style.display !== 'none') {
                App.jokes.hideForm();
            } else {
                form.reset();
                form.querySelector('input[name="id"]').value = '';
                wrap.style.display = '';
                form.querySelector('[name="content"]').focus();
            }
        },

        hideForm() {
            const wrap = document.getElementById('joke-form-wrap');
            const form = document.getElementById('joke-form');
            if (wrap) wrap.style.display = 'none';
            if (form) form.reset();
        }
    },

    // --- Správa uživatelů ---
    users: {
        init() {
            const streamsForm = document.getElementById('streams-form');
            if (streamsForm) {
                streamsForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const inputs = streamsForm.querySelectorAll('input[name]');
                    for (const input of inputs) {
                        const result = await App.api.post('/api/settings', {
                            setting_key: input.name,
                            setting_value: input.value
                        });
                        if (!result || result.error) {
                            alert(result?.error || 'Chyba při ukládání.');
                            return;
                        }
                    }
                    alert('Adresy streamů byly uloženy.');
                });
            }

            const form = document.getElementById('user-form');
            if (!form) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const data = Object.fromEntries(fd.entries());
                const id = data.id;

                let result;
                if (id) {
                    result = await App.api.post('/api/users/update', data);
                } else {
                    delete data.id;
                    result = await App.api.post('/api/users', data);
                }

                if (!result) return;
                if (result.error) { alert(result.error); return; }

                window.location.reload();
            });
        },

        toggleForm() {
            const wrap = document.getElementById('user-form-wrap');
            const form = document.getElementById('user-form');
            if (!wrap || !form) return;
            form.reset();
            form.querySelector('[name="id"]').value = '';
            form.querySelector('[name="password"]').placeholder = 'Heslo (min. 6 znaků)';
            form.querySelector('[name="password"]').required = true;
            wrap.style.display = wrap.style.display === 'none' ? '' : 'none';
        },

        hideForm() {
            const wrap = document.getElementById('user-form-wrap');
            if (wrap) wrap.style.display = 'none';
        },

        edit(tr) {
            const wrap = document.getElementById('user-form-wrap');
            const form = document.getElementById('user-form');
            if (!wrap || !form) return;

            form.querySelector('[name="id"]').value = tr.dataset.id;
            form.querySelector('[name="username"]').value = tr.dataset.username;
            form.querySelector('[name="password"]').value = '';
            form.querySelector('[name="password"]').placeholder = 'Nové heslo (ponechte prázdné pro zachování)';
            form.querySelector('[name="password"]').required = false;
            wrap.style.display = '';
        },

        async remove(id) {
            if (!confirm('Opravdu smazat tohoto uživatele?')) return;
            const result = await App.api.post('/api/users/delete', { id });
            if (!result) return;
            if (result.error) { alert(result.error); return; }
            const row = document.querySelector(`#users-table-body tr[data-id="${id}"]`);
            if (row) row.remove();
        }
    },

    // --- Almanach ---
    almanach: {
        init() {
            const searchInput = document.getElementById('almanach-search');
            if (!searchInput) return;

            this.searchInput = searchInput;
            this.countEl = document.getElementById('almanach-search-count');
            this.tips = document.querySelectorAll('.almanach__tip');
            this.sections = document.querySelectorAll('.almanach__section');
            this.tocLinks = document.querySelectorAll('.almanach__toc-link');

            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => this.search(searchInput.value), 200);
            });

            this.tocLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href').slice(1);
                    const target = document.getElementById(targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            this.initScrollSpy();
        },

        search(query) {
            const q = query.trim().toLowerCase();
            let visibleCount = 0;
            const total = this.tips.length;

            this.tips.forEach(tip => {
                const textEl = tip.querySelector('.almanach__tip-text');
                if (!textEl.dataset.original) {
                    textEl.dataset.original = textEl.textContent;
                }
                const original = textEl.dataset.original;

                if (!q) {
                    textEl.innerHTML = original;
                    tip.classList.remove('is-hidden');
                    visibleCount++;
                    return;
                }

                if (original.toLowerCase().includes(q)) {
                    const regex = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    textEl.innerHTML = original.replace(regex, '<mark>$1</mark>');
                    tip.classList.remove('is-hidden');
                    visibleCount++;
                } else {
                    tip.classList.add('is-hidden');
                }
            });

            this.sections.forEach(section => {
                const visible = section.querySelectorAll('.almanach__tip:not(.is-hidden)');
                section.classList.toggle('is-hidden', visible.length === 0);
            });

            if (this.countEl) {
                this.countEl.textContent = q
                    ? `${visibleCount} z ${total} rad`
                    : '';
            }
        },

        initScrollSpy() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        this.tocLinks.forEach(l => l.classList.remove('is-active'));
                        const active = document.querySelector(`.almanach__toc-link[data-section="${id}"]`);
                        if (active) active.classList.add('is-active');
                    }
                });
            }, { rootMargin: '-15% 0px -75% 0px' });

            this.sections.forEach(section => observer.observe(section));

            // Also observe subsections
            document.querySelectorAll('.almanach__subsection').forEach(sub => observer.observe(sub));
        }
    },

    // --- Scroll to top ---
    scrollTop: {
        init() {
            const btn = document.getElementById('scroll-top-btn');
            if (!btn) return;
            const scrollToTop = () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            window.addEventListener('scroll', () => {
                btn.classList.toggle('is-visible', window.scrollY > 300);
            }, { passive: true });

            btn.addEventListener('click', () => {
                scrollToTop();
            });

            btn.addEventListener('pointerup', (e) => {
                if (e.pointerType === 'mouse') return;
                e.preventDefault();
                scrollToTop();
            });
        }
    },

    // --- Auto-resize textareas ---
    autoResize: {
        init() {
            document.querySelectorAll('textarea.egg-form__note').forEach(ta => {
                ta.style.overflow = 'hidden';
                ta.addEventListener('input', () => App.autoResize.resize(ta));
                App.autoResize.resize(ta);
            });
        },
        resize(ta) {
            if (!ta.offsetHeight) {
                requestAnimationFrame(() => App.autoResize.resize(ta));
                return;
            }
            ta.style.height = 'auto';
            ta.style.height = ta.scrollHeight + 'px';
        }
    },

    // --- Init ---
    init() {
        App.autoResize.init();
        App.hamburger.init();
        App.scrollTop.init();
        App.eggs.init();
        App.charts.init();
        App.notes.init();
        App.gallery.init();
        App.weather.init();
        App.chickens.init();
        App.maintenance.init();
        App.feeding.init();
        App.finance.init();
        App.jokes.init();
        App.users.init();
        App.almanach.init();
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
