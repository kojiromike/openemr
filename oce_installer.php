#!/usr/bin/env php

<?php
/**
 * Collects the settings required to run the OpenEMR Installer, either from the environment or from CLI arguments.
 * Then runs the `quick_install` method.
 *
 * Here are all the possible settings:
 * iuser ?: ''                      // initial user (admin)
 * iuserpass ?: '';                 // initial user password
 * iuname ?: '';                    // initial user display name
 * iufname ?: '';                   // initial user facility name
 * igroup ?: '';                    // initial user group
 * i2faenable ?: '';                // 2FA enable
 * i2fasecret ?: '';                // 2FA TOTP
 * loginhost ?: '';                 // httpd host
 * server ?: '';                    // mysql host
 * port ?: '';                      // mysql port
 * root ?: '';                      // mysql root user
 * rootpass ?: '';                  // mysql root password
 * login ?: '';                     // mysql user
 * pass ?: '';                      // mysql user password
 * dbname ?: '';                    // mysql database name
 * collate ?: '';                   // mysql database collation
 * site ?: 'default';               // initial openemr site
 * source_site_id ?: '';            // source site id for cloning
 * clone_database ?: '';            // clone database
 * no_root_db_access ?: '';         // disable root access to database. user/privileges pre-configured
 * development_translations ?: '';  // use online translations
 * new_theme ?: '';                 // set a new theme for cloned sites
 */
