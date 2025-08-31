#!/bin/bash

# Setup test users for Playwright tests
echo "Setting up test users for Playwright tests..."

# Create or update testadmin user
echo "Creating/updating testadmin user..."
docker exec mobility_wordpress_dev wp user create testadmin testadmin@test.example.com --role=administrator --user_pass="TestAdmin2025!@#" --first_name=Test --last_name=Admin --allow-root 2>/dev/null || \
docker exec mobility_wordpress_dev wp user update testadmin --user_pass="TestAdmin2025!@#" --allow-root

# Create or update jurytester1 user  
echo "Creating/updating jurytester1 user..."
docker exec mobility_wordpress_dev wp user create jurytester1 jury1@test.example.com --role=mt_jury_member --user_pass="JuryTest2025!@#" --first_name=Maria --last_name=Schmidt --allow-root 2>/dev/null || \
docker exec mobility_wordpress_dev wp user update jurytester1 --user_pass="JuryTest2025!@#" --allow-root

# Create or update juryadmintester user
echo "Creating/updating juryadmintester user..."
docker exec mobility_wordpress_dev wp user create juryadmintester juryadmin@test.example.com --role=mt_jury_admin --user_pass="JuryAdmin2025!@#" --first_name=Anna --last_name=Weber --allow-root 2>/dev/null || \
docker exec mobility_wordpress_dev wp user update juryadmintester --user_pass="JuryAdmin2025!@#" --allow-root

echo "Test users setup complete!"
echo ""
echo "Users created/updated:"
echo "  - testadmin (password: TestAdmin2025!@#)"
echo "  - jurytester1 (password: JuryTest2025!@#)"
echo "  - juryadmintester (password: JuryAdmin2025!@#)"