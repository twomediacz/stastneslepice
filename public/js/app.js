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
            const dateStr = date.toLocaleDateString('cs-CZ');
            const noteEsc = App.escapeHtml(record.note || '');

            const html = `<tr data-id="${record.id}" data-date="${record.record_date}" data-count="${record.egg_count}" data-note="${noteEsc}">
                <td>${dateStr}</td>
                <td><span class="egg-count-badge">${record.egg_count}</span></td>
                <td>${noteEsc}</td>
                <td class="egg-actions">
                    <button class="btn-icon" onclick="App.eggs.edit(this.closest('tr'))" title="Upravit">&#x270E;</button>
                    <button class="btn-icon btn-icon--danger" onclick="App.eggs.remove(${record.id})" title="Smazat">&times;</button>
                </td>
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
            if (totalEl) totalEl.textContent = total.toLocaleString('cs-CZ');
            if (avgEl) avgEl.textContent = avg.toLocaleString('cs-CZ', { minimumFractionDigits: 1 }) + ' / den';
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
        },

        async loadClimateChart() {
            const canvas = document.getElementById('chart-climate');
            if (!canvas) return;

            const data = await App.api.get('/api/climate/history?hours=168'); // 7 dní
            if (!data) return;

            const coopData = data.coop || [];
            const outdoorData = data.outdoor || [];

            const labels = coopData.map(r => {
                const d = new Date(r.recorded_at);
                return d.toLocaleDateString('cs-CZ', { day: 'numeric', month: 'numeric' })
                    + ' ' + d.toLocaleTimeString('cs-CZ', { hour: '2-digit', minute: '2-digit' });
            });

            if (App.charts.climateChart) App.charts.climateChart.destroy();

            App.charts.climateChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Teplota kurník (°C)',
                            data: coopData.map(r => r.temperature),
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Vlhkost kurník (%)',
                            data: coopData.map(r => r.humidity),
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            tension: 0.3,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Teplota venku (°C)',
                            data: outdoorData.map(r => r.temperature),
                            borderColor: '#e67e22',
                            backgroundColor: 'rgba(230, 126, 34, 0.1)',
                            tension: 0.3,
                            borderDash: [5, 5],
                            yAxisID: 'y'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            title: { display: true, text: '°C' }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            title: { display: true, text: '%' },
                            grid: { drawOnChartArea: false }
                        },
                        x: {
                            ticks: { maxTicksLimit: 8 }
                        }
                    },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } }
                    }
                }
            });
        },

        async loadEggChart() {
            const canvas = document.getElementById('chart-eggs');
            if (!canvas) return;

            const data = await App.api.get('/api/eggs?days=14');
            if (!data) return;

            const records = (data.records || []).reverse();
            const labels = records.map(r => {
                const d = new Date(r.record_date);
                return d.toLocaleDateString('cs-CZ', { day: 'numeric', month: 'numeric' });
            });
            const values = records.map(r => r.egg_count);

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
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
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
        editingId: null,

        init() {
            const form = document.getElementById('note-form');
            if (!form) return;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                const content = fd.get('content').trim();
                if (!content) return;

                let result;
                if (App.notes.editingId) {
                    result = await App.api.post('/api/notes/update', {
                        id: App.notes.editingId,
                        content: content
                    });
                } else {
                    result = await App.api.post('/api/notes', {
                        content: content,
                        note_date: new Date().toISOString().split('T')[0]
                    });
                }

                if (!result) return;
                if (result.error) { alert(result.error); return; }

                const note = result.note;
                const list = document.getElementById('notes-list');

                if (App.notes.editingId) {
                    const li = list?.querySelector(`li[data-id="${App.notes.editingId}"]`);
                    if (li) {
                        li.dataset.content = note.content;
                        li.querySelector('.note-text').textContent = note.content;
                    }
                    App.notes.editingId = null;
                    form.querySelector('button[type="submit"]').textContent = 'Přidat';
                } else if (list && note) {
                    const d = new Date(note.note_date);
                    const dateStr = d.toLocaleDateString('cs-CZ', { day: 'numeric', month: 'numeric' });
                    const li = document.createElement('li');
                    li.dataset.id = note.id;
                    li.dataset.content = note.content;
                    li.innerHTML = `<strong>${dateStr}</strong> &ndash; <span class="note-text">${App.escapeHtml(note.content)}</span>
                        <span class="note-actions">
                            <button class="btn-icon" onclick="App.notes.edit(this.closest('li'))" title="Upravit">&#x270E;</button>
                            <button class="btn-icon btn-icon--danger" onclick="App.notes.remove(${note.id})" title="Smazat">&times;</button>
                        </span>`;
                    list.prepend(li);
                }
                form.reset();
            });
        },

        edit(li) {
            if (!li) return;
            const form = document.getElementById('note-form');
            if (!form) return;
            const content = li.dataset.content;
            form.content.value = content;
            form.content.focus();
            App.notes.editingId = parseInt(li.dataset.id);
            form.querySelector('button[type="submit"]').textContent = 'Uložit';
        },

        async remove(id) {
            if (!confirm('Smazat poznámku?')) return;
            const result = await App.api.post('/api/notes/delete', { id });
            if (!result) return;
            const li = document.querySelector(`#notes-list li[data-id="${id}"]`);
            if (li) li.remove();
            if (App.notes.editingId === id) {
                App.notes.editingId = null;
                const form = document.getElementById('note-form');
                if (form) {
                    form.reset();
                    form.querySelector('button[type="submit"]').textContent = 'Přidat';
                }
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

    // --- Helpers ---
    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // --- Init ---
    init() {
        App.eggs.init();
        App.charts.init();
        App.notes.init();
        App.gallery.init();
        App.weather.init();
        App.chickens.init();
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
