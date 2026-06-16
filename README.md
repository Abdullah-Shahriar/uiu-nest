# 🏡 UIU Nest — Student Accommodation Platform

<p align="center">
  <img alt="UIU Nest Banner" src="https://via.placeholder.com/1000x200.png?text=UIU+Nest+-+Student+Accommodation" />
</p>

## 📖 Project Overview

**UIU Nest** is a comprehensive student accommodation management platform tailored for United International University (UIU). It serves as a centralized hub bridging the gap between students looking for off-campus housing and property owners seeking reliable tenants. The platform handles browsing, filtering, applications, user roles, and property management seamlessly.

## ✨ Key Features

- **🎓 Student Portal**: Browse available listings, filter by amenities, budget, and distance from UIU. Apply for housing and save favorite listings.
- **🏠 Property Owner Dashboard**: Manage properties, post new room/seat listings, review tenant applications, and assign House Managers.
- **🔑 Role-Based Access Control**: Different access levels including System Admin, Property Owner, House Manager, Tenant, and Student.
- **🎨 Modern UI/UX**: Designed with a responsive interface featuring light/dark mode and an engaging brand color palette.
- **🗺️ Map Integration**: Leaflet map integration to show property locations in relation to the UIU campus.

## 🛠️ Tech Stack

This project is built using a lightweight and fast tech stack:
- **Frontend**: Vanilla JavaScript (ES6), HTML5, Vanilla CSS3 (Custom properties, Flexbox/Grid).
- **Backend**: Vanilla PHP 8+
- **Database**: MySQL (accessed via PDO)
- **Map Library**: Leaflet.js

## 👥 User Roles & Permissions

| Role | Capabilities |
|---|---|
| **Student** | Browse listings, use advanced filters, apply for listings, save favorites. Requires a valid `@uiu.ac.bd` or `@bscse.uiu.ac.bd` email to register. |
| **Tenant** | All student capabilities, plus the ability to post roommate listings. |
| **House Manager** | All tenant capabilities, plus the authority to approve or reject student applications for their assigned property. |
| **Property Owner** | Add and manage properties/listings, review all applications for their properties, and assign existing tenants as House Managers. |
| **System Admin** | Full system oversight, including the approval/rejection of new Property Owner applications. |

## 🔀 Application & Approval Flows

### Student Housing Application
1. **Apply**: A student applies to a listing → Status is marked as `pending`.
2. **Review**: The Property Owner or an assigned House Manager reviews the application.
3. **Approval**: Upon approval, the status changes to `enrolled`. Application timestamps (`applied_at`) are precisely recorded.

### Property Owner Registration
1. Non-owners can click **"Apply to be Owner"** on the login page.
2. They submit an application with their details (Name, Phone, Address, NID photo, Electricity bill, Personal photo).
3. The application goes to the Admin Panel.
4. Once the Admin approves, the user's role is updated to `owner`.

### House Manager Assignment
1. An Owner navigates to their property dashboard.
2. They can select an existing tenant and assign them the **House Manager** role.
3. The newly assigned House Manager can now manage application approvals/rejections for that specific property.

## 🛋️ Amenities & Filtering Logic

### Professional Amenity Set
The platform tracks various amenities to help students find their ideal match:
`High-Speed WiFi`, `Air Conditioning`, `Attached Bathroom`, `Shared Bathroom`, `Fully Furnished`, `Private Balcony`, `Parking Space`, `Laundry Access`, `24/7 Security`, `CCTV Surveillance`, `Study Room`, and `Rooftop Access`.

### Filtering Logic
- **Strict Amenity Matching**: Selecting certain amenities filters out properties that lack them. For instance, filtering by `Security / CCTV` automatically removes properties without those features.
- **Distance to UIU**: The system prioritizes properties closer to the campus (e.g., Madani Avenue) when the nearest filter is applied.
- **Rent Filter**: Students can set rent budgets. Listings range dynamically from budget single seats (৳5,000) to premium double seats (৳14,000+).

## 🚀 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/uiu-nest.git
   ```

2. **Server Environment:**
   - Move the project folder to your local server document root (e.g., `htdocs` for XAMPP, `www` for WAMP).
   - Ensure the folder is named `uiu-nest` or adjust paths accordingly.

3. **Database Configuration:**
   - Create a MySQL database (e.g., `uiu_nest`).
   - Copy `.env.example` to `.env` and update your database credentials if necessary.

4. **Initialize the System:**
   - Open your browser and navigate to the setup script:
     ```
     http://localhost/GitHub/uiu-nest/test_auth.php
     ```
   - *Note: This script automatically builds the database schema, seeds demo data (properties, amenities, demo users), and sets everything up. Warning: Running this script will drop and recreate all tables.*

5. **Launch the Application:**
   - Visit the main entry point: `http://localhost/GitHub/uiu-nest/`

## 🔑 Demo Accounts

Use these accounts to explore the different roles within the system. The password for all accounts is `admin123`.

| User Name | Email Address | Role |
|---|---|---|
| System Admin | `admin@uiu.ac.bd` | **Admin** |
| Shahed Khan | `shahed@gmail.com` | **Owner** |
| Abdullah Shahriar | `shahriar@gmail.com` | **Owner** |
| Ritu Datta | `ritu@bscse.uiu.ac.bd` | **House Manager** |
| Tahsin Faiyaz | `tahsin@bscse.uiu.ac.bd` | **Student (Applicant)** |

## 📁 Project Structure

```text
uiu-nest/
├── api/                  # Backend API endpoints (JSON responses)
├── assets/               # CSS styles and JavaScript files
│   ├── css/              # Modular CSS (base, layout, components, etc.)
│   └── js/               # Frontend logic (app core, maps, listings)
├── config/               # Database connection and environment config
├── includes/             # Shared PHP helpers, header, footer, auth logic
├── pages/                # Frontend views (dashboard, login, profile, admin)
├── sql/                  # Database schemas and migrations
├── uploads/              # User-uploaded files (images, documents)
├── .env.example          # Example environment variables
└── index.php             # Main entry point (Redirects to dashboard)
```

## 🎨 Design System

- **Brand Color:** Vibrant Orange (`#E07820`), complementing the UIU Nest logo.
- **Theming:** Full support for both Light and Dark modes.
  - *Light Mode:* Clean white/gray backgrounds with prominent orange accents.
  - *Dark Mode:* Deep navy backgrounds (`#0f0f1a`) with bright orange highlights (`#f59340`).
- **Responsiveness:** Fully mobile-responsive layout breaking gracefully down to 380px.

## 🛠️ Development Notes & Recent Fixes

- **Authentication Fixes**: Re-configured login queries to safely authenticate users regardless of their `is_active` flag, solving previous lockouts. Fixed POST intercept issues.
- **API Resilience**: Added extensive try-catch blocks in endpoints like `api/listings.php` so PHP errors are returned strictly as JSON to prevent frontend console crashes.
- **Null Safety**: Ensured amenity JSON parsing handles `NULL` values safely (`IFNULL`) to prevent filter crashes.

## 🔮 Next Steps & Roadmap

- [ ] **Notification System**: Real-time alerts for students when application status changes.
- [ ] **Image Uploads**: Complete integration of property image endpoints.
- [ ] **Analytics Dashboard**: Occupancy rate metrics for property owners.
- [ ] **In-App Messaging**: Direct communication channel between applicants and owners/managers.

---
*Developed for United International University.*
