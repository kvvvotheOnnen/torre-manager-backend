#!/bin/sh
set -e

mkdir -p /root/.ssh
cp /ssh-config /root/.ssh/config
cp /ssh-known /root/.ssh/known_hosts
chmod 700 /root/.ssh
chmod 600 /root/.ssh/config /root/.ssh/known_hosts

exec "$@"
