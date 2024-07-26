#!/bin/bash

# Ensure that the script is executable
chmod +x /usr/local/bin/supervisor-init.sh

# Start the supervisor
sudo supervisorctl
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start horizon