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
            if (avgEl) avgEl.textContent = avg.toLocaleString('cs-CZ', { minimumFractionDigits: 1 });
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

        async loadClimateChart(period) {
            period = period || 'day';
            const canvas = document.getElementById('chart-climate');
            if (!canvas) return;

            let labels, datasets;

            if (period === 'day') {
                const data = await App.api.get('/api/climate/history?hours=24');
                if (!data) return;
                const coopData = data.coop || [];
                const outdoorData = data.outdoor || [];
                labels = coopData.map(r => {
                    const d = new Date(r.recorded_at);
                    return d.toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit' });
                });
                datasets = [
                    {
                        label: 'Teplota kurník (°C)',
                        data: coopData.map(r => r.temperature),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.3, yAxisID: 'y'
                    },
                    {
                        label: 'Vlhkost kurník (%)',
                        data: coopData.map(r => r.humidity),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.3, yAxisID: 'y1'
                    },
                    {
                        label: 'Teplota venku (°C)',
                        data: outdoorData.map(r => r.temperature),
                        borderColor: '#e67e22',
                        backgroundColor: 'rgba(230, 126, 34, 0.1)',
                        tension: 0.3, borderDash: [5, 5], yAxisID: 'y'
                    }
                ];
            } else {
                let url, dateKey;
                if (period === 'year') {
                    url = '/api/climate/history?group=month&months=12';
                    dateKey = 'month';
                } else {
                    const daysMap = { week: 7, month: 30 };
                    url = '/api/climate/history?group=day&days=' + (daysMap[period] || 7);
                    dateKey = 'date';
                }
                const data = await App.api.get(url);
                if (!data) return;
                const coopData = data.coop || [];
                const outdoorData = data.outdoor || [];

                if (dateKey === 'month') {
                    labels = coopData.map(r => {
                        const [y, m] = r.month.split('-');
                        const d = new Date(parseInt(y), parseInt(m) - 1);
                        return d.toLocaleDateString('cs-CZ', { month: 'short' });
                    });
                } else {
                    labels = coopData.map(r => {
                        const d = new Date(r.date + 'T00:00:00');
                        return d.toLocaleDateString('cs-CZ', { day: 'numeric', month: 'numeric' });
                    });
                }

                const minLine = { borderWidth: 1, borderDash: [3, 3], pointRadius: 0 };
                const maxLine = { borderWidth: 2, pointRadius: 2 };

                datasets = [
                    {
                        label: 'Teplota kurník (°C)',
                        data: coopData.map(r => parseFloat(r.temp_max)),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.15)',
                        fill: '+1', tension: 0.3, yAxisID: 'y', ...maxLine
                    },
                    {
                        label: '_coopTempMin',
                        data: coopData.map(r => parseFloat(r.temp_min)),
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.15)',
                        fill: false, tension: 0.3, yAxisID: 'y', ...minLine
                    },
                    {
                        label: 'Vlhkost kurník (%)',
                        data: coopData.map(r => parseFloat(r.hum_max)),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.15)',
                        fill: '+1', tension: 0.3, yAxisID: 'y1', ...maxLine
                    },
                    {
                        label: '_coopHumMin',
                        data: coopData.map(r => parseFloat(r.hum_min)),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.15)',
                        fill: false, tension: 0.3, yAxisID: 'y1', ...minLine
                    },
                    {
                        label: 'Teplota venku (°C)',
                        data: outdoorData.map(r => parseFloat(r.temp_max)),
                        borderColor: '#e67e22',
                        backgroundColor: 'rgba(230, 126, 34, 0.15)',
                        fill: '+1', tension: 0.3, borderDash: [5, 5], yAxisID: 'y', ...maxLine
                    },
                    {
                        label: '_outTempMin',
                        data: outdoorData.map(r => parseFloat(r.temp_min)),
                        borderColor: '#e67e22',
                        backgroundColor: 'rgba(230, 126, 34, 0.15)',
                        fill: false, tension: 0.3, yAxisID: 'y', ...minLine
                    }
                ];
            }

            if (App.charts.climateChart) App.charts.climateChart.destroy();

            App.charts.climateChart = new Chart(canvas, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            type: 'linear', position: 'left',
                            title: { display: true, text: '°C' }
                        },
                        y1: {
                            type: 'linear', position: 'right',
                            title: { display: true, text: '%' },
                            grid: { drawOnChartArea: false }
                        },
                        x: {
                            ticks: { autoSkip: period === 'day', maxTicksLimit: period === 'day' ? 8 : undefined }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                filter: (item) => !item.text.startsWith('_')
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    if (ctx.dataset.label.startsWith('_')) return null;
                                    return ctx.dataset.label + ': ' + ctx.formattedValue;
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
                        backgroundColor: records.map((_, i) => {
                            const colors = ['#e74c3c', '#e67e22', '#f1c40f', '#2ecc71', '#3498db', '#9b59b6'];
                            return colors[i % colors.length];
                        }),
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
        init() {
            const form = document.getElementById('note-form');
            if (!form) return;

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
            wrap.style.display = '';
            form.content.focus();
        },

        async remove(id) {
            if (!confirm('Smazat poznámku?')) return;
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
                <td>${dateStr}</td>
                <td>${contentEsc}</td>
                ${noteActionsHtml}
            </tr>`;

            const existingRow = tbody.querySelector(`tr[data-id="${note.id}"]`);
            if (existingRow) {
                existingRow.outerHTML = html;
            } else {
                tbody.insertAdjacentHTML('afterbegin', html);
            }
        }
    },

    // --- Galerie ---
    gallery: {
        init() {
            const input = document.getElementById('photo-upload');
            if (!input) return;

            // Klik na fotku otevře lightbox
            document.getElementById('gallery-grid')?.addEventListener('click', (e) => {
                const item = e.target.closest('.gallery-item');
                if (!item || e.target.closest('.gallery-item__delete')) return;
                const img = item.querySelector('img');
                if (img) {
                    // Zobrazit plnou verzi (ne thumbnail)
                    const fullSrc = img.src.replace('/uploads/thumbs/', '/uploads/');
                    App.gallery.openLightbox(fullSrc);
                }
            });

            // Zavřít lightbox klávesou Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') App.gallery.closeLightbox();
            });

            input.addEventListener('change', async () => {
                const file = input.files[0];
                if (!file) return;

                const fd = new FormData();
                fd.append('photo', file);

                const result = await App.api.upload('/api/photos', fd);
                if (!result) return;
                if (result.error) { alert(result.error); return; }

                const photo = result.photo;
                const grid = document.getElementById('gallery-grid');
                if (grid && photo) {
                    const div = document.createElement('div');
                    div.className = 'gallery-item';
                    div.dataset.id = photo.id;
                    div.innerHTML = `<img src="/uploads/thumbs/${App.escapeHtml(photo.filename)}"
                        alt="${App.escapeHtml(photo.caption || '')}" loading="lazy"
                        onerror="this.onerror=null;this.src='/uploads/${App.escapeHtml(photo.filename)}'">`;
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
            const form = document.getElementById('chicken-form');
            if (!form) return;

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
                form.status.value = data.status || 'active';
                form.birth_date.value = data.birth_date || '';
                form.acquired_date.value = data.acquired_date || '';
                form.end_date.value = data.end_date || '';
                form.note.value = data.note || '';
                form.querySelector('input[name="id"]').value = data.id;
            } else {
                title.textContent = 'Přidat slepici';
                form.querySelector('input[name="id"]').value = '';
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
            form.querySelector('input[name="note"]').value = tr.dataset.note;
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
            form.querySelector('input[name="note"]').value = tr.dataset.note;
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
            form.querySelector('input[name="note"]').value = tr.dataset.note;
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
            form.querySelector('input[name="note"]').value = tr.dataset.note;
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
            form.querySelector('input[name="note"]').value = tr.dataset.note;

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

    // --- Init ---
    init() {
        App.hamburger.init();
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
