# UIU Nest — Student Accommodation Platform

## 📖 Project Overview

**UIU Nest** is a comprehensive student accommodation management platform tailored specifically for United International University (UIU). It acts as a centralized ecosystem connecting students seeking off-campus housing with property owners seeking reliable tenants. The platform handles end-to-end property management, including browsing, dynamic filtering, application tracking, move-out processes, complaint management, and two-way review systems.

## ✨ Complete Feature List

The system goes beyond basic listings and provides a full-suite property management experience.

### 🎓 For Students & Tenants
- **Advanced Listing Search & Filtering**: Filter by rent budget (e.g., ৳5,000–৳14,000+), specific amenities, and distance to UIU campus (with map integration).
- **AI-Powered Search**: Utilize the integrated AI search endpoint (`api/ai_search.php`) to find listings naturally.
- **Application Tracking**: Apply to listings and track status (`pending`, `enrolled`, `rejected`).
- **Roommate Finder**: Once a tenant, students can post "Roommate Needed" listings for their specific rooms.
- **Save Listings**: Bookmark favorite properties to review later.
- **Tenant Dashboard (`my-home.php`)**: A dedicated portal to view current residence details, room capacity, and co-tenants.
- **Complaints System**: Submit maintenance, noise, safety, or management complaints to the property owner/manager (with an anonymous submission option).
- **Move-Out Requests**: Initiate formal move-out requests from their current room.

### 🏠 For Property Owners & House Managers
- **Property Management**: Create and manage multiple properties with location coordinates (Leaflet map), descriptions, and cover photos with draggable cropping.
- **Room & Listing Management**: Add rooms (with specific rent, capacities, and amenities) and publish them as listings.
- **Listing Requirements**: Set strict tenant requirements (Preferred Gender, Department, Study Year, Smoking/Pet policies).
- **Application Review System**: Approve or reject incoming student applications.
- **House Manager Assignment**: Owners can delegate authority by assigning an existing tenant to act as the "House Manager" for a specific property.
- **Announcements & Calendar Events**: Broadcast announcements, maintenance schedules, or "Rent Due" notices to all tenants of a property.
- **Occupancy Tracking**: Monitor which rooms are vacant, partially filled, or at capacity.

### ⭐ Comprehensive Review & Rating System
The platform features a specialized two-way review lifecycle tied to the move-out process:
1. **Move-out Initialization**: Tenant requests to move out.
2. **Owner Reviews Tenant**: The owner rates the departing tenant on Cleanliness, Behaviour, and Punctuality (1-5 stars). These aggregate into a public "Student Rating".
3. **Tenant Reviews Property**: The departing tenant reviews the property on Cleanliness, Safety, and Value for Money (1-5 stars). These become public "Resident Reviews".

### 🔑 Security & Role-Based Access Control
- **Domain-Restricted Registration**: Student registration strictly requires valid UIU emails (`@uiu.ac.bd`, `@bscse.uiu.ac.bd`, `@student.uiu.ac.bd`). Allowed domains are managed via the database.
- **Owner Application Workflow**: Prospective owners must apply with NID, Electricity Bill, and a personal photo. Admins must manually approve them before they gain Owner privileges.
- **Granular Roles**: 
  - `Guest`: Can browse public listings.
  - `Student`: Can apply to listings and save favorites.
  - `Tenant`: Can post roommate listings and use the "My Home" dashboard.
  - `House Manager`: Tenant with elevated privileges to manage applications for their property.
  - `Owner`: Full control over their properties, rooms, listings, and tenants.
  - `Admin`: System oversight and approval of new owners.

## 🛋️ Amenities & Strict Filtering Logic
Properties can be tagged with professional amenities: High-Speed WiFi, AC, Attached Bath, Shared Bath, Furnished, Balcony, Parking, Laundry, 24/7 Security, CCTV, Study Room, Rooftop Access, Generator, and Elevator/Lift.

**Smart Filtering**: Selecting an amenity acts as a strict filter. For example, selecting `Security / CCTV` automatically removes any properties in the database that lack those specific tags.

## 🛠️ Tech Stack & Architecture

- **Frontend**: Vanilla JavaScript (ES6), HTML5, Vanilla CSS3 (Custom properties, Flexbox/Grid). Fully responsive mobile-first design breaking down to 380px.
- **Backend**: Vanilla PHP 8+ handling API endpoints (`/api`) and view logic (`/pages`).
- **Database**: MySQL (accessed via PDO) with robust foreign-key constraints and cascading deletes.
- **Integrations**: Leaflet.js for interactive mapping.
- **Design System**: Brand color is Vibrant Orange (`#E07820`). Features a native toggle for Light and Dark modes.

## 📁 Project Structure

```text
uiu-nest/
├── api/                  # Backend API endpoints (JSON responses, auth, CRUD, AI)
├── assets/               # CSS styles and JavaScript files
│   ├── css/              # Modular CSS (base, layout, components, profile, etc.)
│   └── js/               # Frontend logic (app.js, listings.js, map.js)
├── config/               # Database connection and environment variables
├── includes/             # Shared PHP helpers, header, footer, auth checks
├── pages/                # Frontend views (dashboard, profiles, admin, my-home)
├── sql/                  # Database schemas and full migration data
├── uploads/              # User-uploaded files (images, documents, NIDs)
├── .env.example          # Example environment variables
├── index.php             # Main entry point (Redirects to dashboard)
└── test_auth.php         # Database setup and reset script
```

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/uiu-nest.git
   ```

2. **Server Environment:**
   - Move the project folder to your local server document root (e.g., `htdocs` for XAMPP, `www` for WAMP).
   - Ensure the folder is named `uiu-nest` or adjust paths accordingly.

3. **Database Configuration:**
   - Create a MySQL database.
   - Copy `.env.example` to `.env` and update your database credentials.

4. **Initialize the System:**
   - Open your browser and navigate to the setup script:
     ```
     http://localhost/GitHub/uiu-nest/test_auth.php
     ```
   - *Note: This script drops existing tables, rebuilds the full schema from `schema.sql`, and seeds demo properties, rooms, amenities, and users.*

5. **Launch the Application:**
   - Visit the main entry point: `http://localhost/GitHub/uiu-nest/`

## 🔑 Demo Accounts

Use these accounts to explore the different roles within the system. The password for all accounts is `admin123`.

| User Name | Email Address | Role |
|---|---|---|
| System Admin | `admin@uiu.ac.bd` | **Admin** |
| Shahed Khan | `shahed@gmail.com` | **Owner** |
| Abdullah Shahriar | `shahriar@gmail.com` | **Owner** |
| Ritu Datta | `ritu@bscse.uiu.ac.bd` | **House Manager** (Tenant) |
| Tahsin Faiyaz | `tahsin@bscse.uiu.ac.bd` | **Student** (Applicant) |

## 🛠️ Development Notes & API Resilience
- **API Architecture**: Endpoints in `api/` are wrapped in try-catch blocks to strictly return JSON, preventing frontend console crashes when PHP errors occur.
- **Authentication Resilience**: Login queries safely bypass `is_active` lockouts and properly prioritize POST requests over GET redirects.
- **Null Safety**: Amenity JSON parsing handles `NULL` values safely (`IFNULL`) to prevent database query failures during filtering.

---
*Developed for United International University.*
