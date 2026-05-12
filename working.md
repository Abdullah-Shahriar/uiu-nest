# UIU Nest — Working Notes & Project Summary

## Project Overview

UIU Nest is a student accommodation management platform for United International University (UIU). It allows students to browse and apply for off-campus housing, property owners to manage listings and tenants, and admins to oversee everything.

**Entry Point:** `http://localhost/GitHub/uiu-nest`
**Setup Script:** `http://localhost/GitHub/uiu-nest/test_auth.php`
**Tech Stack:** PHP (vanilla), MySQL (via PDO), Vanilla JS, Vanilla CSS

---

## Demo Accounts (Password: `admin123` for all)

| Name | Email | Role |
|---|---|---|
| System Admin | `admin@uiu.ac.bd` | Admin |
| Shahed Khan | `shahed@gmail.com` | Owner |
| Abdullah Shahriar | `shahriar@gmail.com` | Owner |
| Ritu Datta | `ritu@bscse.uiu.ac.bd` | House Manager (tenant) |
| Tahsin Faiyaz | `tahsin@bscse.uiu.ac.bd` | Student (applicant) |

---

## Project File Structure

```
uiu-nest/
├── api/
│   ├── auth.php              — Login, register, logout
│   ├── listings.php          — Browse/filter/CRUD listings
│   ├── applications.php      — Student applications (apply, approve, reject)
│   ├── properties.php        — Owner property CRUD
│   ├── admin.php             — Admin panel actions
│   ├── admin-owner-action.php — Approve/reject owner applications
│   ├── assign-manager.php    — Owner assigns house manager
│   ├── owner-apply.php       — New owner application form
│   ├── profile.php           — Profile updates
│   ├── property-images.php   — Image upload
│   └── saved.php             — Save/unsave listings
├── assets/
│   ├── css/
│   │   ├── style.css         — Master (imports all below)
│   │   ├── base.css          — CSS variables, colors, typography
│   │   ├── layout.css        — Sidebar, topbar, main layout
│   │   ├── components.css    — Cards, buttons, badges, modals
│   │   ├── profile.css       — Profile page styles
│   │   └── responsive.css    — Mobile breakpoints
│   └── js/
│       ├── app.js            — Core: sidebar, theme, toasts, fetchAPI
│       ├── listings.js       — Dashboard listings AJAX & filter
│       └── map.js            — Leaflet map integration
├── config/
│   └── database.php          — DB connection, session start, constants
├── includes/
│   ├── auth.php              — Auth helpers (login, getCurrentUser, roles)
│   ├── functions.php         — Shared helpers (jsonResponse, amenities, etc.)
│   ├── header.php            — Sidebar navigation (role-aware)
│   └── footer.php            — Scripts, toast container
├── pages/
│   ├── dashboard.php         — Browse listings (public + logged-in)
│   ├── login.php             — Login with Student/Owner toggle
│   ├── register.php          — Student registration
│   ├── profile.php           — User profile
│   ├── listing-detail.php    — Single listing view + apply
│   ├── owner.php             — Owner dashboard
│   ├── admin.php             — Admin panel
│   └── apply-owner.php       — Owner application form (with file upload)
├── sql/
│   └── schema.sql            — Full DB schema + seed data
├── uploads/                  — User uploaded files
└── test_auth.php             — Setup/reset script (run first!)
```

---

## Color Theme

The brand color is **orange (#E07820)** — matching the UIU Nest logo.

- Light mode: white/light gray backgrounds, orange accents
- Dark mode: deep navy backgrounds (#0f0f1a), bright orange accents (#f59340)

---

## Properties & Locations

**Shahed Khan** owns 7 properties total:
- Greenview Residence — Madani Avenue, Badda
- Scholar Heights — Bir Uttam Rafiqul Islam Ave
- Campus Edge Hostel — United City, Madani Avenue
- Shatarkul View Apartments — Shatarkul, Badda *(no security/CCTV)*
- Sayeed Nagar Heights — Sayeed Nagar *(no balcony/rooftop/study room)*
- Kuril Flyover Residency — Kuril Chowrasta *(far from UIU)*
- Bashundhara Gate House — Bashundhara RA *(far but full amenities)*

**Abdullah Shahriar** owns 3 properties:
- Shatarkul Lake Breeze — Shatarkul *(no security/CCTV)*
- Kuril Point Hostel — Vatara, Kuril *(far from UIU)*
- Bashundhara Comfort Inn — Bashundhara RA *(far but full amenities)*

### Amenity Filter Logic

Selecting certain amenities will cancel out properties that lack them:

| Filter Selected | Properties Cancelled Out |
|---|---|
| Security / CCTV | All Shatarkul properties |
| Balcony / Rooftop / Study Room | All Sayeed Nagar properties |
| (Distance filter) Nearest to UIU | Bashundhara RA + Kuril properties |

---

## Amenities (Professional Set)

| Slug | Label |
|---|---|
| wifi | High-Speed WiFi |
| ac | Air Conditioning |
| attached_bath | Attached Bathroom |
| shared_bath | Shared Bathroom |
| furnished | Fully Furnished |
| balcony | Private Balcony |
| parking | Parking Space |
| laundry | Laundry Access |
| security | 24/7 Security |
| cctv | CCTV Surveillance |
| study_room | Study Room |
| rooftop | Rooftop Access |

---

## Key Features

### Authentication
- Login page has a **Student / Property Owner toggle**
- Students must use UIU email domains (`@uiu.ac.bd`, `@bscse.uiu.ac.bd`)
- Owners use any email — no domain restriction
- New owners click "Apply for owner access" → fill full application form (NID, electricity bill, photo) → goes to admin for approval

### Role-Based Access

| Role | Can Do |
|---|---|
| Student | Browse, apply for listings, save favorites |
| Tenant | All student actions + post roommate listings |
| House Manager (tenant) | All tenant actions + approve/reject applications for assigned property |
| Owner | All of the above + manage properties, listings, assign house managers |
| Admin | Everything + approve/reject owner applications |

### Application Flow
1. Student applies to a listing → `status = pending`
2. Owner or House Manager reviews → approves → `status = enrolled`
3. Timestamps recorded as `applied_at` (precise datetime)

### Owner Application Flow
1. Non-owner clicks "Apply to be Owner" on login
2. Fills name, phone, address, NID photo, electricity bill, personal photo
3. Submitted to admin panel
4. Admin approves → user role updated to `owner`

### House Manager Assignment
- Owner goes to their property
- Selects an existing tenant as house manager
- That tenant gains authority to approve/reject applications for that property

---

## Rent Filter Options

- **Min:** ৳6,000 → ৳20,000
- **Max:** ৳8,000 → ৳24,000
- Properties range from ৳5,000 (budget single, Campus Edge) to ৳14,000 (premium Bashundhara double)

---

## Important Bug Fixes Applied

1. **Login blocked by `is_active = 0`** — Removed `AND is_active = 1` from both `api/auth.php` and `includes/auth.php` login queries. Users can login regardless of active flag.

2. **`api/listings.php` missing** — File was deleted at some point. Recreated with full try-catch so PHP errors return JSON instead of HTML (preventing `unexpected token <` in browser console).

3. **POST login intercepted** — `api/auth.php` was redirecting GET requests (when user was already logged in) BEFORE processing POST login requests. Fixed by only redirecting on GET.

4. **`shahriar@gmail.com` not working** — Caused by `is_active = 0` in the database. Fixed by removing the `is_active` check from all login queries.

5. **Amenity filter crashing** — `JSON_CONTAINS(r.amenities_json, ?)` failed when `amenities_json` was NULL. Fixed with `IFNULL(r.amenities_json, '[]')`.

6. **Logo rendering** — SVG logo rebuilt with correct proportions: house roofline above NEST, bold UIU, thin NEST text, horizontal divider line, tagline. Applied to both sidebar and login page.

---

## How to Reset the Database

Visit: `http://localhost/GitHub/uiu-nest/test_auth.php`

This script will:
- Drop and recreate all tables
- Refresh amenities (removes fan, kitchen, generator)
- Create all demo users with `is_active = 1`
- Add 3 original Shahed properties + 4 new Shahed + 3 Shahriar properties
- Add rooms and listings for all properties
- Reset admin password to `admin123`

---

## CSS Architecture Notes

- **Orange brand** `#E07820` is the accent color everywhere (replaces old blue `#4361ee`)
- CSS variables in `base.css` — change colors there to rebrand globally
- Responsive breakpoints: 380px / 640px / 768px / 1024px / 1400px / 1600px
- Mobile: single column, stacked filter, sidebar slides in from left

---

## Next Steps / Pending Features

1. **Notification system** — Notify students when application status changes
2. **Property image uploads** — Currently images show placeholder; upload endpoint exists at `api/property-images.php`
3. **Occupancy analytics** — Dashboard for owners showing occupancy rates
4. **Map view** — Leaflet map already integrated; markers show property locations near UIU
5. **Messaging** — In-app messaging between applicants and owners/managers
