# modern-campus-api
Scripts for testing or working with the Modern Campus CMS API

## Calendar: Pending Approval Events
Script that fetches pending approval events 

### Local Dev
- Create `cache` directory inside cloned repo
- Duplicate `omni_api.config` to `omni_api.config.local` and place username/password in file
- Uncomment DEV config require and comment out the PROD require in `calendar-pending/index.php`
