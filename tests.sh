#!/bin/bash

BASE_DIR=$(dirname $0)
CAKE_DIR=${BASE_DIR}/cakephp
CAKE_CONSOLE=$CAKE_DIR/lib/Cake/Console/cake

$CAKE_CONSOLE -app "$BASE_DIR" testsuite app $@
