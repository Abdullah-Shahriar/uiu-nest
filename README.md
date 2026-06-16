# UIU Nest — Advanced Student Accommodation Platform

## 📖 Project Overview

**UIU Nest** is an advanced, fully-featured student accommodation management ecosystem tailored specifically for United International University (UIU). It provides an end-to-end management flow starting from listing creation, filtering, AI search, all the way to complex multi-step application approvals, tenant move-ins, daily complaints, rent tracking, and an interactive move-out/review lifecycle.

## ✨ Exhaustive Feature List

### 🎓 For Students & Tenants
- **Intelligent Discovery:**
  - **Dynamic Filtering:** Search by precise rent ranges (৳5,000 to ৳14,000+), specific professional amenities (e.g., Security, Generator), and geographical distance to UIU.
  - **AI-Powered Natural Language Search:** (`api/ai_search.php`) Leverage Groq API/OpenAI to find listings using conversational queries (e.g., "Find me a quiet room with a balcony under 8k").
  - **Map Integration:** Leaflet.js rendering UIU campus markers and accurately plotting property coordinates.
- **Applications & Management:**
  - **Track Applications:** Full lifecycle tracking (`pending`, `enrolled`, `rejected`).
  - **Profile Avatar System:** Interactive canvas-based profile picture upload featuring zoom, pan, and a circular-to-square JPEG cropping tool.
  - **Save & Bookmark:** Pin favorite properties for later review.
- **Tenant Dashboard ("My Home"):**
  - View current tenancy details, roommate information, and room capacities.
  - Submit detailed, categorized complaints (Maintenance, Noise, Safety) with an **Anonymous Submission** toggle.
  - **Roommate Finder:** Active tenants can publish "Roommate Needed" sub-listings for their specific rooms.
  - **Move-out Workflow:** Tenants initiate formal move-out requests triggering a dedicated review pipeline.

### 🏠 For Property Owners & House Managers
- **Property & Listing Management:**
  - Create and manage properties, mapping them with lat/lng coordinates.
  - **Draggable Cover Photos:** Advanced CSS `object-position` implementation allowing owners to upload and perfectly crop/position property banner images.
  - **Lightbox Gallery:** Integrated native lightbox for property image galleries.
- **Listing Requirements Engine:**
  - Strictly define tenant criteria: Preferred Gender, Department, Study Year (Min/Max), Smoking Rules, and Pet Policies.
- **Application Processing:**
  - Multi-tier approval system. Owners review student profiles (including verification documents) and approve/reject them.
- **Delegation System:**
  - Owners can assign trustworthy existing tenants to the **House Manager** role, granting them the authority to process applications for that specific property.
- **Announcements & Calendar Event System:**
  - Push global property announcements, "Rent Due" notices, or "Maintenance" warnings directly to all tenants.

### ⭐ Closed-Loop Review & Rating System
The platform features a highly specialized two-way evaluation tied to the move-out process:
1. **Initiation**: The tenant submits a formal move-out request.
2. **Owner Evaluates Tenant**: The landlord rates the departing tenant (1-5 stars) specifically on *Cleanliness, Behaviour, and Punctuality*. This aggregates into a global "Student Rating" visible on their profile.
3. **Tenant Evaluates Property**: The tenant rates the property (1-5 stars) specifically on *Cleanliness, Safety, and Value for Money*. This becomes a public "Resident Review" visible to future applicants.
4. **Completion**: Only after both reviews are completed is the move-out finalized in the database.

### 🔑 Security, Architecture, & Access Control
- **Domain-Restricted Auth**: Strict enforcement limiting registration to authenticated UIU emails (`@uiu.ac.bd`, `@bscse.uiu.ac.bd`, etc.), governed by a dynamic `allowed_domains` database table.
- **Rigorous Owner Vetting**: Non-student property owners must submit an extensive application (NID, Electricity Bill, Photo) requiring manual admin verification before account activation.
- **Role Hierarchy**: 
  1. `Guest` (Public search)
  2. `Student` (Apply)
  3. `Tenant` (My Home, Complaints, Roommates)
  4. `House Manager` (Review applications for assigned property)
  5. `Owner` (Full property lifecycle management)
  6. `Admin` (System-wide configuration, domain management, owner approvals)

### 🌍 UI/UX & Localization Enhancements
- **Theming**: Fully responsive native CSS implementation supporting both Light and Dark modes.
- **Brand Identity**: Features the signature UIU Nest Vibrant Orange (`#E07820`).
- **Localization**: Built-in Google Translate integration offering instant English ↔ Bangla language switching.
- **Live Widgets**: Persistent live clock and real-time interface elements integrated into the top bar.

## 🛋️ Amenities & Smart Strict Filtering
Properties are classified using a professional set of vector (SVG) icon-backed amenities: High-Speed WiFi, AC, Attached Bath, Shared Bath, Furnished, Balcony, Parking, Laundry, 24/7 Security, CCTV, Study Room, Rooftop Access, Generator, and Elevator/Lift.
- **Strict Logic**: Filtering is absolute. Selecting `CCTV` completely removes any matching rooms/properties from the UI that do not explicitly possess the `CCTV` JSON flag in the database.

## 🛠️ Tech Stack & Resilience

- **Frontend**: Vanilla JavaScript (ES6), HTML5, Vanilla CSS3 (Custom properties, Flexbox/Grid). Mobile-first responsiveness (down to 380px breakpoints).
- **Backend**: Vanilla PHP 8+ handling API endpoints (`/api`) and multi-page routing (`/pages`).
- **API Resilience**: All backend APIs (`/api/*.php`) heavily utilize `try/catch` wrappers. Instead of crashing the frontend with unparsed PHP errors, the system ensures strictly formatted JSON responses, even during fatal database failures. Null-safety implemented natively in SQL (e.g., `IFNULL` on amenity JSON parsing).
- **Database**: MySQL (via PDO) leveraging full InnoDB relational constraints, foreign keys, and cascading deletes across 20 distinct tables (`schema.sql`).
- **Dependencies**: Leaflet.js (Mapping), Google Fonts (Inter, Outfit), Google Translate API.

## 📁 Project Structure

```text
uiu-nest/
├── api/                  # Pure JSON endpoints (AI search, CRUD, Auth, Uploads)
├── assets/               # Frontend core
│   ├── css/              # Modular architecture (base, layout, components, profile)
│   └── js/               # Modular logic (app.js, map.js, listings.js, cover-photo.js, lightbox.js)
├── config/               # DB connection & environment (.env)
├── includes/             # Shared view logic (header, footer, auth, UI widgets)
├── pages/                # Protected & Public Views (dashboard, my-home, admin)
├── sql/                  # Relational DB Schema (`schema.sql` + historical migrations)
├── scratch/              # Temporary server workspace for scripts
├── uploads/              # Isolated directory for secure file/avatar uploads
├── .env.example          # Environment variable template (DB creds, Groq API keys)
├── index.php             # System entry router
└── test_auth.php         # Developer script to instantly reset/seed the database
```

## 🚀 Installation & Setup

1. **Clone & Mount:**
   - Clone the repository into your local web server's document root (e.g., `htdocs` for XAMPP).
   - Ensure the directory is precisely named `uiu-nest`.

2. **Environment Configuration:**
   - Create an empty MySQL database.
   - Duplicate `.env.example` to `.env`.
   - Add your database credentials and, optionally, your Groq API key (for the AI Search feature).

3. **Database Initialization:**
   - Navigate to the setup utility: `http://localhost/GitHub/uiu-nest/test_auth.php`
   - *Warning: Executing this script drops all tables and executes a pristine build from `schema.sql`, pre-loading all demo properties, rooms, users, and amenities.*

4. **Launch:**
   - Visit the main entry point: `http://localhost/GitHub/uiu-nest/`

## 🔑 Demo Accounts (Password: `admin123`)

| User Name | Email Address | Role |
|---|---|---|
| System Admin | `admin@uiu.ac.bd` | **Admin** |
| Shahed Khan | `shahed@gmail.com` | **Owner** |
| Abdullah Shahriar | `shahriar@gmail.com` | **Owner** |
| Ritu Datta | `ritu@bscse.uiu.ac.bd` | **House Manager** (Tenant) |
| Tahsin Faiyaz | `tahsin@bscse.uiu.ac.bd` | **Student** (Applicant) |

---
*Developed for United International University.*
