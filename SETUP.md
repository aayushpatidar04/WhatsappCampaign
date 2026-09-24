# WhatsApp Campaign Manager - Setup Guide

## Step 1: Run Migrations
```
php artisan migrate
```

## Step 2: Install PhpSpreadsheet (for Excel parsing)
```
composer require phpoffice/phpspreadsheet
```
Then run:
```
composer dump-autoload
```

## Step 3: Configure Login
In your `.env`:
```
AUTH_USERNAME=admin
AUTH_PASSWORD=your_secure_password
```

## Step 4: Create a WhatsApp Account
1. Go to http://localhost (login)
2. Click "Accounts" in navbar
3. Click "Add Account"
4. Fill in:
   - Account Name: e.g. "Arihant Capital"
   - Phone Number ID: From Meta Business Manager
   - Business ID: (optional) Your WABA ID
   - Access Token: Permanent or temporary token
   - API Version: e.g. v25.0
   - Webhook Verify Token: (for status callbacks)

## Step 5: Create a Template
1. Go to "Templates" in navbar
2. Click "Add Template"
3. Fill in:
   - Account: Select the one you just created
   - Template Name: Must match Meta template name exactly (e.g. "diwali_greeting")
   - Language Code: e.g. en_IN
   - Header Type: none/text/image/video/document
   - Body Variables: e.g. "name, order_id, amount"
     (This maps to {{1}}, {{2}}, {{3}} in your Meta template)
   - Has Document Header: Check if you'll upload per-user PDFs

## Step 6: Create a Campaign
1. Click "New Campaign"
2. Select your WhatsApp Account
3. Select your Template (template name/language auto-fill)
4. Upload Excel (.xlsx/.csv) with columns like:
   - Phone | Name | OrderID | Amount
   - 9876543210 | Rahul | 12345 | 5000
   - 9876543211 | Priya | 12346 | 7500
   OR paste numbers manually
5. Optional: Upload a .zip with files named by phone number:
   - 9876543210.pdf
   - 9876543211.pdf
6. Click "Create Campaign"

## Step 7: Send
- On the campaign detail page, click "Start Campaign"
- This dispatches jobs to the queue

## Step 8: Process Queue
Open a terminal and run:
```
php artisan queue:work --tries=1 --timeout=0
```

## Note on .env Changes
- You NO LONGER need to change .env for each campaign
- All credentials are stored in the database per account
- The template name and language are per-campaign (selected at campaign creation)
- Access tokens are ENCRYPTED in the database

## Setting up Webhook
Point Meta's webhook to: `https://your-domain.com/webhook/whatsapp`
- GET: Meta sends hub.verify_token for verification
- POST: Meta sends status updates (sent, delivered, read, failed)

## Database Schema
- `whatsapp_accounts` - stores encrypted API credentials per account
- `campaign_templates` - template definitions per account with variable list
- `campaigns` - linked to account + template, stores column_mapping JSON
- `campaign_messages` - per-recipient with variables JSON
- `campaign_files` - per-user file attachments from zip

## File Structure
- Excel: Column names become variable keys (case-insensitive match)
- Variables are stored as JSON per message row
- Template variables define the ORDER of {{1}}, {{2}}, etc.
- Body variables from Excel columns are mapped by column_mapping
