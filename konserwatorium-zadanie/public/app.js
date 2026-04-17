const API_BASE = '/api/';

async function fetchJSON(action, params = {}) {
    const url = new URL(API_BASE, window.location.origin);
    url.searchParams.set('action', action);

    Object.entries(params).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            url.searchParams.set(key, value);
        }
    });

    const response = await fetch(url);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error ?? `HTTP ${response.status}`);
    }

    return data;
}

const PAGE_SIZE = 20;
let allEvents = [];
let currentPage = 1;

async function loadEvents() {
    const container = document.getElementById('events-container');
    container.innerHTML = '<p>Ładowanie...</p>';

    try {
        allEvents = await fetchJSON('events', {
            city:     document.getElementById('city').value.trim(),
            dateFrom: document.getElementById('dateFrom').value,
            dateTo:   document.getElementById('dateTo').value,
            category: document.getElementById('category').value,
        });
        currentPage = 1;
        renderEventsTable(container);
    } catch (e) {
        showError(container, e.message);
    }
}

function renderEventsTable(container) {
    if (allEvents.length === 0) {
        container.innerHTML = '<p>Brak wyników dla podanych filtrów.</p>';
        return;
    }

    const totalPages = Math.ceil(allEvents.length / PAGE_SIZE);
    const pageEvents = allEvents.slice((currentPage - 1) * PAGE_SIZE, currentPage * PAGE_SIZE);

    const table = document.createElement('table');
    table.innerHTML = `
        <thead>
            <tr>
                <th>ID eventu</th><th>Data</th><th>Miasto</th>
                <th>Kategoria</th><th>Sprzedane bilety</th>
            </tr>
        </thead>
    `;

    const tbody = document.createElement('tbody');
    pageEvents.forEach(event => {
        const row = document.createElement('tr');
        ['eventId', 'eventDate', 'city', 'category', 'totalTickets'].forEach(key => {
            const cell = document.createElement('td');
            cell.textContent = event[key];
            row.appendChild(cell);
        });
        tbody.appendChild(row);
    });

    table.appendChild(tbody);

    const pagination = document.createElement('div');
    pagination.className = 'pagination';
    pagination.innerHTML = `
        <button ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">&#8249; Poprzednia</button>
        <span>Strona ${currentPage} z ${totalPages} (${allEvents.length} wyników)</span>
        <button ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">Następna &#8250;</button>
    `;

    container.innerHTML = '';
    container.appendChild(table);
    container.appendChild(pagination);
}

function changePage(page) {
    currentPage = page;
    renderEventsTable(document.getElementById('events-container'));
}

async function loadUtmRanking() {
    const container = document.getElementById('utm-container');

    try {
        const data = await fetchJSON('utm-ranking');
        renderUtmTable(container, data);
    } catch (e) {
        showError(container, e.message);
    }
}

async function loadUtmRankingConfirmed() {
    const container = document.getElementById('utm-confirmed-container');

    try {
        const data = await fetchJSON('utm-ranking-confirmed');
        renderUtmTable(container, data);
    } catch (e) {
        showError(container, e.message);
    }
}

function renderUtmTable(container, campaigns) {
    const table = document.createElement('table');
    table.innerHTML = `
        <thead><tr><th>#</th><th>Kampania</th><th>Łącznie biletów</th></tr></thead>
    `;

    const tbody = document.createElement('tbody');
    campaigns.forEach(item => {
        const row = document.createElement('tr');
        ['rank', 'campaign', 'totalTickets'].forEach(key => {
            const cell = document.createElement('td');
            cell.textContent = item[key];
            row.appendChild(cell);
        });
        tbody.appendChild(row);
    });

    table.appendChild(tbody);
    container.innerHTML = '';
    container.appendChild(table);
}

function showError(container, message) {
    container.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'error';
    p.textContent = 'Błąd: ' + message;
    container.appendChild(p);
}

document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    loadUtmRanking();
    loadUtmRankingConfirmed();
});
