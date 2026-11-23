# GEARORA Full QA Test Matrix

Tanggal: 2026-11-23

| Area | Coverage | Result | Bukti |
|---|---|---:|---|
| Public routes | home, katalog, guide, trip, auth | PASS | `RouteSmokeTest`, Playwright |
| Trip Planner | validation, experience, date derivation, flexible mode | PASS | `TripPlannerTest`, `TripPlannerWizardTest` |
| Package checkout | rental, sale, mixed, idempotency, tamper validation | PASS | `TripPackageTest` |
| Customer auth | success, failed credentials, ownership | PASS | Feature + Playwright |
| Admin auth/RBAC | owner/admin/staff/customer boundaries | PASS | `AdminRouteAuthorizationTest`, `RbacAndBookingTest` |
| Guides | list, detail, filter, preview, media upload | PASS | Guide feature tests + Playwright |
| Guide security | raw HTML, iframe, javascript URL rejection | PASS | `GuideContentRendererTest`, `AdminGuideStudioTest` |
| Payments | status display and payment workflows | PASS | payment feature suites |
| Rental | period, availability, stock/assets, concurrency | PASS | MySQL integration |
| Sales | stock reservation and checkout | PASS | sales feature suites |
| PDF/export | package PDF and report authorization | PASS | PDF/export suites |
| CSRF/rate limit | web middleware, login throttling | PASS | security feature suites |
| Responsive | desktop and mobile viewport, overflow, drawer | PASS | Playwright |
| Build | Vite production bundle | PASS | `npm run build` |

Not tested: live Midtrans settlement, production SMTP, real payment gateway callback. Test environment uses local SQLite/MySQL test DB.
