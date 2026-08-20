/**
 * AllHotels.lk — main.js
 * Shared front-end interactions: mobile nav, star-rating picker,
 * booking date guard, and lightweight form UX helpers.
 */

document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Mobile nav toggle ---------- */
    const navToggle = document.getElementById('navToggle');
    const mainNav = document.getElementById('mainNav');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('open');
        });
    }

    /* ---------- Interactive star rating pickers ----------
       Markup expected:
       <div class="star-select" data-input="#ratingInput">
         <span data-value="1">★</span> ... <span data-value="5">★</span>
       </div>
       <input type="hidden" id="ratingInput" name="rating" value="5">
    ---------------------------------------------------------- */
    document.querySelectorAll('.star-select').forEach(function (widget) {
        const targetSelector = widget.getAttribute('data-input');
        const input = targetSelector ? document.querySelector(targetSelector) : null;
        const stars = Array.from(widget.querySelectorAll('span'));

        function paint(value) {
            stars.forEach(function (s) {
                s.classList.toggle('active', parseInt(s.dataset.value, 10) <= value);
            });
        }

        const initial = input ? parseInt(input.value || '5', 10) : 5;
        paint(initial);

        stars.forEach(function (s) {
            s.addEventListener('click', function () {
                const val = parseInt(s.dataset.value, 10);
                if (input) input.value = val;
                paint(val);
            });
            s.addEventListener('mouseenter', function () {
                paint(parseInt(s.dataset.value, 10));
            });
        });

        widget.addEventListener('mouseleave', function () {
            paint(input ? parseInt(input.value, 10) : initial);
        });
    });

    /* ---------- Booking form: block past dates ---------- */
    document.querySelectorAll('input[type="date"][data-min-today]').forEach(function (el) {
        const today = new Date().toISOString().split('T')[0];
        el.setAttribute('min', today);
    });

    /* ---------- Confirm before destructive admin actions ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('submit', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    /* ---------- Auto-dismiss alerts ---------- */
    document.querySelectorAll('.alert[data-autohide]').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 4000);
    });

    /* ---------- Gallery thumbnail -> main image swap ---------- */
    const mainImg = document.getElementById('mainHotelImage');
    document.querySelectorAll('.gallery-strip img').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (mainImg) mainImg.src = thumb.src;
        });
    });
});
