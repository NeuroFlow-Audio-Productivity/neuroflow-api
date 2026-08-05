# NeuroFlow deployment guide

This guide deploys the two NeuroFlow repositories without requiring a custom domain:

- `neuroflow-api`: Laravel Cloud
- `neuroflow-front`: Cloudflare Pages

Provider-generated HTTPS domains are enough for the first deployment. A custom domain can be connected later.

## 1. Before deploying

1. Push the latest `feature/core` branch from both repositories to GitHub.
2. Create a Laravel Cloud account using the GitHub account that can access `neuroflow-api`.
3. Create a Cloudflare account using the GitHub account that can access `neuroflow-front`.
4. Do not commit `.env`, API keys, passwords, or provider credentials.

## 2. Deploy the API on Laravel Cloud

1. Create an application from the `neuroflow-api` GitHub repository.
2. Select `feature/core` as the production branch for the first deployment.
3. Select PHP 8.4 and a US East region. Virginia is a reasonable first choice for users in Brazil.
4. Use a Flex compute size with Scale to Zero while traffic is low.
5. Attach a Laravel MySQL Flex database in the same region.
6. Attach a private Laravel Object Storage bucket and make it the default filesystem disk.
7. Generate an application key locally without changing `.env`:

   ```bash
   php artisan key:generate --show
   ```

8. Add these environment variables in Laravel Cloud. Replace all placeholder URLs and secrets:

   ```dotenv
   APP_NAME=Neuroflow
   APP_ENV=production
   APP_KEY=base64:generated-key
   APP_DEBUG=false
   APP_URL=https://your-api.laravel.cloud
   FRONTEND_URL=https://your-front.pages.dev
   CORS_ALLOWED_ORIGINS=https://your-front.pages.dev
   LOG_CHANNEL=stack
   LOG_LEVEL=info
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   MAIL_MAILER=log
   GOOGLE_CLIENT_ID=
   GOOGLE_CLIENT_SECRET=
   GOOGLE_REDIRECT_URI=https://your-front.pages.dev/auth/google/callback
   ```

   Laravel Cloud injects the attached MySQL and object-storage credentials. Do not copy local database or AWS credentials into the dashboard.

9. Configure the deploy command:

   ```bash
   php artisan migrate --force && php artisan db:seed --force
   ```

   The current seeders use `updateOrCreate` and `sync`, so they may run after each deployment without duplicating the default profiles, items, or modes.

10. Deploy and verify:

    ```text
    https://your-api.laravel.cloud/up
    https://your-api.laravel.cloud/docs/api
    ```

`MAIL_MAILER=log` is acceptable only for the initial infrastructure test. Configure a transactional email provider before testing public registration, email verification, or password reset.

## 3. Deploy the frontend on Cloudflare Pages

1. In Cloudflare, open **Workers & Pages**, create a Pages project, and import `neuroflow-front` from GitHub.
2. Select `feature/core` as the production branch for the first deployment.
3. Configure:

   ```text
   Build command: npm run build
   Build output directory: dist
   Node.js version: 22
   ```

4. Add these production build variables:

   ```dotenv
   VITE_API_BASE_URL=https://your-api.laravel.cloud/api
   VITE_GOOGLE_OAUTH_REDIRECT_URI=https://your-front.pages.dev/auth/google/callback
   ```

5. Deploy. The `public/_redirects` file makes Vue Router routes such as `/auth/login` and `/core` work when opened or refreshed directly.

## 4. Connect the final provider URLs

The final Cloudflare URL is only known after its first deployment. Update the Laravel Cloud variables with that exact URL:

```dotenv
FRONTEND_URL=https://your-final-front.pages.dev
CORS_ALLOWED_ORIGINS=https://your-final-front.pages.dev
GOOGLE_REDIRECT_URI=https://your-final-front.pages.dev/auth/google/callback
```

Redeploy the API after changing variables. If the API URL also changed, update `VITE_API_BASE_URL` in Cloudflare and redeploy the frontend.

## 5. Configure Google OAuth

In Google Cloud Console, create a Web application OAuth client and register this exact authorized redirect URI:

```text
https://your-final-front.pages.dev/auth/google/callback
```

Copy the client ID and secret into Laravel Cloud. Never expose the client secret through a frontend variable.

## 6. Production readiness checklist

- API `/up` returns a successful response.
- Vue routes work after a browser refresh.
- Registration creates a user.
- A real email provider delivers verification and password-reset messages.
- Password and Google logins work.
- Audio upload and playback still work after redeploying the API.
- MySQL daily backups are enabled.
- `APP_DEBUG` is `false`.
- Billing alerts or spending limits are enabled.
- No secrets exist in either Git repository.

## 7. Add a custom domain later

A simple final layout is:

```text
app.example.com -> Cloudflare Pages
api.example.com -> Laravel Cloud
```

After connecting those domains, update the API URL, frontend URL, CORS origin, and Google authorized redirect URI to use the custom HTTPS domains.
