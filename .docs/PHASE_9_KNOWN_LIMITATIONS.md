# PHASE 9 — Known Limitations

1. Mixed trip package creates two payments: one rental order and one sales order. Intentional risk control; no combined Midtrans charge.
2. Package document is summary, not payment receipt.
3. Wizard is progressive UI only. Browser reload loses client-side current step; submitted recommendation state remains server-rendered.
4. Guide inline images must be uploaded via Studio (`admin.guides.media.store`) or already exist under `media/...`; Markdown hotlinks to external images are intentionally rejected.
5. Browser smoke used disposable SQLite. Authenticated checkout was exercised
   with the transfer method only; production Midtrans was not invoked.
6. Headless Chrome in this environment locks the viewport (~1264px), so true
   mobile-viewport pixel layout was not verified in a browser; mobile coverage
   relies on CSS breakpoints and automated navigation/mobile tests.
7. No Figma file access. Visual work follows Phase 9 audit/design brief; pixel-perfect claim not made.
8. Production deployment/hosting validation requires owner/operator access.
