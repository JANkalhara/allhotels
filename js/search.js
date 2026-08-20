/**
 * AllHotels.lk — search.js
 * Drives the home-page filter form: submits criteria to /api/search.php
 * via fetch and re-renders the hotel grid without a full page reload.
 */

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('searchForm');
    const grid = document.getElementById('hotelGrid');
    const countEl = document.getElementById('resultsCount');
    if (!form || !grid) return;

    function starHtml(rating) {
        const rounded = Math.round(rating || 0);
        let out = '';
        for (let i = 1; i <= 5; i++) out += i <= rounded ? '★' : '☆';
        return out;
    }

    function cardTemplate(hotel) {
        const badge = hotel.is_premium == 1
            ? '<div class="premium-badge">★ Premium</div>'
            : '';
        const img = hotel.main_image
            ? `<img src="/${hotel.main_image}" alt="${hotel.name}">`
            : `<span>${hotel.name}</span>`;
        const tags = (hotel.functions || '').split(',').filter(Boolean).slice(0, 3).join(' | ');

        return `
        <article class="hotel-card">
            <div class="thumb">${badge}${img}</div>
            <div class="body">
                <h3>${hotel.name}</h3>
                <div class="addr">📍 ${hotel.address}, ${hotel.district}</div>
                <div class="tags"><span class="tag">${tags || 'Venue'}</span></div>
                <div class="card-meta">
                    <span class="price">From Rs. ${Number(hotel.starting_price).toLocaleString()}</span>
                    <span class="rating">${starHtml(hotel.avg_rating)} ${hotel.avg_rating ? Number(hotel.avg_rating).toFixed(1) : 'New'}</span>
                </div>
                <a class="btn btn-primary btn-block" href="/hotel-details.php?id=${hotel.id}">View Details</a>
            </div>
        </article>`;
    }

    async function runSearch() {
        const params = new URLSearchParams(new FormData(form));
        grid.setAttribute('aria-busy', 'true');
        try {
            const res = await fetch('/api/search.php?' + params.toString());
            const data = await res.json();

            if (!data.hotels || data.hotels.length === 0) {
                grid.innerHTML = '<div class="empty-state">No hotels match your search. Try widening your filters.</div>';
            } else {
                grid.innerHTML = data.hotels.map(cardTemplate).join('');
            }
            if (countEl) countEl.textContent = data.hotels.length + ' hotel(s) found';
        } catch (err) {
            grid.innerHTML = '<div class="empty-state">Something went wrong loading hotels. Please try again.</div>';
        } finally {
            grid.removeAttribute('aria-busy');
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        runSearch();
    });

    // Live filter on select/range changes for a snappier feel
    form.querySelectorAll('select, input[type="number"]').forEach(function (el) {
        el.addEventListener('change', runSearch);
    });
});
