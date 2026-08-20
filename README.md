# AllHotels.lk — Web Platform

A PHP + MySQL implementation of the AllHotels.lk system spec: public hotel
discovery with search/filter, Free vs Premium hotel listings, customer
reviews, Premium online booking, owner dashboard, and an admin approval
panel.

## Tech stack
- **PHP** 8+ (PDO/MySQL, sessions, no framework)
- **MySQL / MariaDB**
- **Vanilla CSS** (`css/style.css`) — no framework
- **Vanilla JS** (`js/main.js`, `js/search.js`) — no framework

## Folder structure
```
allhotels/
├── admin/          Admin panel (dashboard, hotel approval, users, moderation)
├── api/            AJAX / form-post endpoints (search, review, booking)
├── auth/           Register / Login / Logout
├── config/db.php   Database connection (edit credentials here)
├── css/style.css   All styling
├── js/             main.js (UI) + search.js (AJAX filter)
├── includes/       header.php, footer.php, functions.php (shared PHP)
├── owner/          Owner dashboard, add hotel, gallery, bookings, reviews
├── sql/schema.sql  Full database schema + seed data
├── uploads/hotels/ Uploaded hotel images
├── index.php       Home page (search/filter + hotel grid)
├── hotel-details.php  Unified Free/Premium hotel details view
├── about.php / contact.php
```

## Setup
1. Create the database:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
2. Edit `config/db.php` with your DB host/user/password.
3. Point your PHP server's document root at this folder (e.g. `php -S localhost:8000` from inside `allhotels/`, or configure Apache/Nginx).
4. Visit `/auth/register.php?type=owner` to create an owner account, add a
   hotel, then log in as admin (seeded account: `admin@allhotels.lk` —
   set a real password by updating the `password_hash` column, since the
   seeded hash is a placeholder) to approve it.
5. Approved hotels appear on the home page automatically. Toggle a hotel to
   **Premium** from Admin → Hotels Control to unlock gallery + booking.

## Notes
- Notifications are logged to the `notifications` table (visible under
  Owner → Notifications Log) rather than sending real email/WhatsApp —
  wire up `notify()` in `includes/functions.php` to real providers
  (e.g. an SMTP library, Twilio/WhatsApp Business API) for production use.
- Image uploads are stored under `uploads/hotels/` — make sure this folder
  is writable by the web server.
