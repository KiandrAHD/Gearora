# PHASE 9 — Known Limitations

1. Mixed trip package creates two payments: one rental order and one sales order. Intentional risk control; no combined Midtrans charge.
2. Package document is summary, not payment receipt.
3. Wizard is progressive UI only. Browser reload loses client-side current step; submitted recommendation state remains server-rendered.
4. Guide inline images must be uploaded via Studio (`admin.guides.media.store`) or already exist under `media/...`; Markdown hotlinks to external images are intentionally rejected.
5. Browser smoke used disposable SQLite and did not run authenticated checkout/payment lifecycle or production Midtrans.
6. No Figma file access. Visual work follows Phase 9 audit/design brief; pixel-perfect claim not made.
7. Production deployment/hosting validation requires owner/operator access.
