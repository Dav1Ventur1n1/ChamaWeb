#!/bin/bash
set -e
DATE=$(date +%Y%m%d_%H%M)
BACKUP_DIR="./backups"
mkdir -p "$BACKUP_DIR"
mysqldump -h db -u root -proot chamaweb > "$BACKUP_DIR/backup_${DATE}.sql"
