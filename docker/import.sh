#!/usr/bin/env bash

psql -U postgres -c "DROP DATABASE db;"
psql -U postgres -c "CREATE DATABASE db;"

psql -U postgres db -f "/tmp/docker/pgsql-structure.sql"
psql -U postgres db -f "/tmp/docker/pgsql-account-table.sql"
psql -U postgres db -f "/tmp/docker/pgsql-data.sql"
