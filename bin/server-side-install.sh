#!/usr/bin/env bash
#
# WalleePayment server side install
#
# This is for internal use only. DO NOT USE THIS.
#
php ../../../../bin/console wallee:settings:install --applicationKey $WALLEE_APPLICATION_KEY --spaceId $WALLEE_SPACE_ID --userId $WALLEE_USER_ID
php ../../../../bin/console wallee:order-delivery-states:install
php ../../../../bin/console wallee:payment-method:configuration
php ../../../../bin/console wallee:payment-method:default
php ../../../../bin/console wallee:webhooks:install