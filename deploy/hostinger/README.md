# Deploying to Hostinger (shared hosting)

Folder layout on the server (replace `icss-eshet-tabulation.pitonmain.com`):

```
/home/u988863428/domains/icss-eshet-tabulation.pitonmain.com/
├── icss/            <- the whole Laravel project (NOT web-accessible)
│   ├── app/ bootstrap/ config/ database/ resources/ routes/ storage/ vendor/ ...
│   └── .env         <- copy of .env.production
└── public_html/     <- web root
    ├── index.php    <- from deploy/hostinger/public_html/index.php
    ├── .htaccess    <- from deploy/hostinger/public_html/.htaccess
    ├── build/       <- copied from icss/public/build
    ├── img/         <- copied from icss/public/img
    ├── favicon.ico  <- copied from icss/public/favicon.ico
    └── robots.txt   <- copied from icss/public/robots.txt
```

See the chat instructions (or the main README) for the step-by-step commands.
