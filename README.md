# modern-campus-api
Scripts for testing or working with the Modern Campus CMS API

## Calendar: Pending Approval Events
Script that fetches pending approval events 

### Endpoints
- All Events: `/events`
- Date Range: `/events?start={date}&end={date}` - Date in YYYY-MM-DD format
- Single Event: `/events/{event-id}`

### Production Setup 
- Create `cache` directory inside cloned repo. Set group to www-data and permissions to 775
- Duplicate `omni_api.config.example` to `omni_api.config`, place username/password in file, and move to configs folder
- May have to set location rewrite rule in NGINX to pass paramaters to script

### Local Dev
- Create `cache` directory inside cloned repo. Set group to www-data and permissions to 775
- Duplicate `omni_api.config.example` to `omni_api.config.local` and place username/password in file
- Uncomment DEV config require and comment out the PROD require in `calendar-pending/index.php`
