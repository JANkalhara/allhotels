<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';

// Districts for the dropdown
$districts = $pdo->query("SELECT DISTINCT district FROM hotels WHERE status='approved' ORDER BY district")->fetchAll();
$functionTypes = $pdo->query("SELECT * FROM function_types ORDER BY name")->fetchAll();

// Initial (server-rendered) hotel list — same query logic as api/search.php
$hotels = $pdo->query("
    SELECT h.*,
           (SELECT image_path FROM hotel_images WHERE hotel_id = h.id AND is_main = 1 LIMIT 1) AS main_image,
           (SELECT AVG(rating) FROM reviews WHERE hotel_id = h.id) AS avg_rating,
           (SELECT GROUP_CONCAT(ft.name SEPARATOR ',') FROM hotel_function_types hft
                JOIN function_types ft ON ft.id = hft.function_type_id WHERE hft.hotel_id = h.id) AS functions
    FROM hotels h
    WHERE h.status = 'approved'
    ORDER BY h.is_premium DESC, h.created_at DESC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/slider.php';
?>

<section class="hero">
    <div class="container">
        
        <form class="search-panel" id="searchForm">
            <div class="search-field">
                <label for="q">Search Hotel</label>
                <input type="text" id="q" name="q" placeholder="Hotel name or keyword">
            </div>
            <div class="search-field">
                <label for="district">Location</label>
                <select id="district" name="district">
                    <option value="">All Districts</option>
                    <?php foreach ($districts as $d): ?>
                        <option value="<?= h($d['district']) ?>"><?= h($d['district']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label for="function_type">Function Type</label>
                <select id="function_type" name="function_type">
                    <option value="">Any</option>
                    <?php foreach ($functionTypes as $ft): ?>
                        <option value="<?= (int)$ft['id'] ?>"><?= h($ft['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label for="guests">Guest Capacity</label>
                <input type="number" id="guests" name="guests" min="1" placeholder="e.g. 150">
            </div>
            <div class="search-field">
                <label for="max_price">Max Price (Rs.)</label>
                <input type="number" id="max_price" name="max_price" min="0" step="1000" placeholder="e.g. 50000">
            </div>
            <button type="submit" class="search-btn">Search Hotels</button>
        </form>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>Featured &amp; Available Hotels</h2>
                <p>Premium partners are highlighted first with richer galleries and instant booking.</p>
            </div>
            <span class="results-count" id="resultsCount"><?= count($hotels) ?> hotel(s) found</span>
        </div>

        <div class="hotel-grid" id="hotelGrid">
            <?php if (empty($hotels)): ?>
                <div class="empty-state">No hotels listed yet. Check back soon!</div>
            <?php else: foreach ($hotels as $hotel): ?>
                <article class="hotel-card">
                    <div class="thumb">
                        <?php if ($hotel['is_premium']): ?><div class="premium-badge">★ Premium</div><?php endif; ?>
                        <?php if ($hotel['main_image']): ?>
                            <img src="/<?= h($hotel['main_image']) ?>" alt="<?= h($hotel['name']) ?>">
                        <?php else: ?>
                            <span><?= h($hotel['name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="body">
                        <h3><?= h($hotel['name']) ?></h3>
                        <div class="addr">📍 <?= h($hotel['address']) ?>, <?= h($hotel['district']) ?></div>
                        <div class="tags">
                            <?php
                            $tags = array_filter(explode(',', $hotel['functions'] ?? ''));
                            foreach (array_slice($tags, 0, 3) as $t): ?>
                                <span class="tag"><?= h($t) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-meta">
                            <span class="price">From Rs. <?= number_format($hotel['starting_price']) ?></span>
                            <span class="rating"><?= star_html($hotel['avg_rating'] ?? 0) ?> <?= $hotel['avg_rating'] ? number_format($hotel['avg_rating'], 1) : 'New' ?></span>
                        </div>
                        <a class="btn btn-primary btn-block" href="/allhotels/hotel-details/hotel-details.php?id=<?= (int)$hotel['id'] ?>">View Details</a>
                    </div>
                </article>
            <?php endforeach; endif; ?>
        </div>
    </div>
</section>

<?php
$extra_scripts = ['/js/search.js'];
require_once __DIR__ . '/includes/footer.php';
?>
