=== Custom Request ===

Version: 1.0.0
Requires PHP: 7.4
Stable tag: 1.0.0
Tested up to: 6.7.1
License: GPLv2 or later

# Custom Request
This plugin was made in order to fetch data from endpoints and display it on posts

# Set-up
## Install dependencies
`npm install`

## Build style
`npm run build`

## Run
`sudo docker-compose up`

# Access Redis command line
`sudo docker exec -it container_custom_wp redis-cli -h redis`

# To-do list
- [x] Create custom post
- [x] Make request
- [x] Replace shortcut by data
- [x] Implement Redis
- [x] Convert style to sass
- [ ] Implement tests
- [ ] Add feature to fetch data from time to time